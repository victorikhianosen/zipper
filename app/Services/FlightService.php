<?php

namespace App\Services;

use App\Comms\Amadeus;
use App\Enums\Status\FlightStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\CustomException;
use App\Http\Requests\FlightBookingRequest;
use App\Http\Resources\FlightBookingResource;
use App\Jobs\ResellerCallBackJob;
use App\Models\BookFlight;
use App\Models\City;
use App\Models\FlightOffers;
use App\Models\FlightSetting;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class FlightService extends BaseService
{
    protected FlightSetting $flightSetting;

    public function __construct(
        protected Amadeus $amadeus,
        protected City $city,
        protected FlightOffers $flightOffers,
        protected BookFlight $bookFlight,
        protected FlightSetting $setting,
        protected Setting $generalSetting
    ) {
        $this->flightSetting = $this->setting->first(); // @phpstan-ignore-line
    }

    /**
     * @param  array <string, mixed>  $data
     * @return array<string,mixed>
     */
    public function getOffers(array $data): array|Collection
    {

        $search_id = $data['search_id'];
        $fromCity = $this->city->where('city_code', $data['from'])->first()?->city_name;
        $toCity = $this->city->where('city_code', $data['to'])->first()?->city_name;
        $payload = [
            'originLocationCode' => $data['from'], 'destinationLocationCode' => $data['to'],
            'departureDate' => $data['departure_date'], 'adults' => $data['no_of_adult'],
            'children' => $data['no_of_children'], 'infants' => $data['no_of_infant'],
            'travelClass' => $data['flight_class'], 'nonStop' => $data['stop_over'],
            'currencyCode' => 'NGN', 'max' => 2,
        ];
        if ($data['trip_type'] == 'round') {
            $payload['returnDate'] = $data['arrival_date'];
        }
        if (filled($data['airlines'])) {
            $payload['includedAirlineCodes'] = $data['airlines'];
        }
        if (filled($data['max_price'])) {
            $payload['maxPrice'] = intval($data['max_price']);
        }
        $offers = $this->amadeus->searchOffers($payload);
        if (blank($offers)) {
            throw new CustomException('Search doesn\'t bring results');
        }

        foreach ($offers['data'] as $offer) {

            $this->flightOffers->create([
                'search_id' => $search_id,
                'itineraries' => $offer['itineraries'],
                'price' => $offer['price'],
                'pricing_option' => $offer['pricingOptions'],
                'traveler_pricing' => $offer['travelerPricings'],
                'raw_offer_response' => $offer,
                'locations' => [
                    'from_code' => $data['from'],
                    'from_city' => $fromCity,
                    'to_code' => $data['to'],
                    'to_city' => $toCity,
                ],
                'search_meta' => $data,
                'dictionaries' => $offers['dictionaries'],
            ]);
        }

        $fromDb = $this->flightOffers->where('search_id', $search_id)->get();

        return $fromDb;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCities(): array
    {
        $filter = @ucfirst(request()->query('filter')); //@phpstan-ignore-line
        $query = filled($filter) ? $this->city->where('city_name', 'like', "%$filter%") : $this->city;

        return $this->paginate(
            query: $query,
            columns: ['id', 'city_name', 'city_code', 'country_code']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function validatePrice(FlightOffers $offer): array
    {

        $rawOffer = $offer?->raw_offer_response; //@phpstan-ignore-line

        $payload = [
            'data' => [
                'type' => 'flight-offers-pricing',
                'flightOffers' => [$rawOffer],
            ],
        ];

        $amadeusValidatedDetail = $this->amadeus->validatePrice($payload);
        if (! array_key_exists('data', $amadeusValidatedDetail) || ! array_key_exists('flightOffers', $amadeusValidatedDetail['data'])) {
            throw new CustomException('Unable to validate price, try again');
        }
        $price = $amadeusValidatedDetail['data']['flightOffers'][0]['price'];
        $offer->update(['price' => $price]); //@phpstan-ignore-next-line
        $breakdown_by_passenger = collect($amadeusValidatedDetail['data']['flightOffers'][0]['travelerPricings'])
            ->map(function ($item) {
                return [
                    'passenger_id' => $item['travelerId'],
                    'fare_option' => $item['fareOption'],
                    'passenger_type' => $item['travelerType'],
                    'price' => $item['price']['total'],
                ];
            })
            ->toArray();

        return [
            'price' => $price,
            'breakdown_by_passenger' => $breakdown_by_passenger,
        ];
    }

    /**
     * @return array<string, mixed> | \App\Models\BookFlight
     */
    public function bookFlight(FlightBookingRequest $request): array|BookFlight
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $is_reseller = $user->is_reseller;
        $data = $request->validated();
        $rawOffer = FlightOffers::whereId($data['offer_id'])->first();
        throw_if(blank($rawOffer), CustomException::class, 'Offer refreshed, please restart your search');
        $request->validatePassengerAge($rawOffer->itineraries); //@phpstan-ignore-line
        $meta = $this->buildFlightMeta($data);
        $payload = [
            'data' => [
                'type' => 'flight-order',
                'flightOffers' => [$rawOffer->raw_offer_response],
                'travelers' => $meta['travelers'],
                'remarks' => $meta['remarks'],
                'ticketingAgreement' => $meta['ticketing'],
                'contacts' => $meta['contacts'],
            ],
        ];

        // $trxn = $this->verifyFlightPayment($data['offer_id'], $data['payment_reference']);
        // $paymentStatus = $trxn->status;
        // record pending booking
        $pendingBooking = $this->createPendingFlightBooking($rawOffer, $payload, $data);

        // if ($paymentStatus == TransactionStatus::PENDING->value) {
        //     $pendingBooking->update(['offer_id' => $rawOffer->id]);
        //     $trxn->update(['book_flight_id' => $pendingBooking->id, 'temp_booking_ref' => $pendingBooking->id]);
        //     throw new CustomException('Payment is processing, retry in few moments');
        // }
        // if ($paymentStatus == TransactionStatus::FAILED->value) {
        //     $pendingBooking->update(['offer_id' => $rawOffer->id, 'payment_status' => TransactionStatus::FAILED->value]);
        //     $trxn->update(['book_flight_id' => $pendingBooking->id, 'temp_booking_ref' => null]);
        //     throw new CustomException('Flight Booking payment failed');
        // }
        // $this->requestForAmadeusBooking(
        //     payload: $payload, data: $data,
        //     pendingBooking: $pendingBooking,
        //     rawOffer: $rawOffer, trxn: $trxn
        // );

        if (filled('is_reseller') && $is_reseller == true) {
            dispatch(new ResellerCallBackJob(new FlightBookingResource($pendingBooking), $user));
        }

        return ['booking_id' => $pendingBooking->id];
    }

    /**
     * @return array<string, mixed> | \App\Models\BookFlight
     */
    public function completeFlightBooking(): array|BookFlight
    {
        /** @var \App\Models\User $user */
        $user = request()->user();
        $is_reseller = $user->is_reseller;
        $data = request()->validate([
            'booking_id' => 'bail|required|exists:book_flights,id',
        ]);

        $trxn = Transaction::whereBookFlightId($data['booking_id'])
            ->latest('updated_at')->first();
        $pendingBooking = BookFlight::whereId($data['booking_id'])->first();
        throw_if(blank($trxn), CustomException::class, 'Payment not found');
        throw_if($trxn->status == TransactionStatus::PENDING->value, CustomException::class, 'Payment is processing');
        if ($trxn->status == TransactionStatus::FAILED->value) {
            $pendingBooking?->update(['payment_status' => TransactionStatus::FAILED->value]);
            throw new CustomException('Payment was not successful');
        }
        $rawOffer = FlightOffers::whereId($pendingBooking?->offer_id)->first();
        throw_if(blank($rawOffer), CustomException::class, 'Offer refreshed, please start again');
        $this->requestForAmadeusBooking(
            payload: $pendingBooking->request_body, //@phpstan-ignore-line
            data: ['offer_id' => $pendingBooking?->offer_id],
            rawOffer: $rawOffer,
            pendingBooking: $pendingBooking, //@phpstan-ignore-line
            trxn: $trxn
        );
        if (filled('is_reseller') && $is_reseller == true) {
            dispatch(new ResellerCallBackJob(new FlightBookingResource($pendingBooking), $user));
        }

        return ['booking_id' => $pendingBooking?->id];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function buildFlightMeta(array $data): array
    {
        //@phpstan-ignore-next-line
        $travelers = collect($data['passengers'])->map(function ($item) {
            return [
                'id' => strval($item['id']),
                'dateOfBirth' => $item['date_of_birth'],
                'name' => [
                    'firstName' => $item['name']['first_name'],
                    'lastName' => $item['name']['last_name'],
                ],
                'gender' => $item['gender'],
                'contact' => [
                    'emailAddress' => $item['contact']['email_address'],
                    'phones' => [[
                        'deviceType' => $item['contact']['phones'][0]['device_type'],
                        'countryCallingCode' => $item['contact']['phones'][0]['country_calling_code'],
                        'number' => $item['contact']['phones'][0]['number'],
                    ]],
                ],

                'documents' => [
                    [
                        'documentType' => $item['documents'][0]['document_type'],
                        'birthPlace' => $item['documents'][0]['birth_place'],
                        'issuanceLocation' => $item['documents'][0]['issuance_location'],
                        'issuanceDate' => $item['documents'][0]['issuance_date'],
                        'number' => $item['documents'][0]['number'],
                        'expiryDate' => $item['documents'][0]['expiry_date'],
                        'issuanceCountry' => $item['documents'][0]['issuance_country'],
                        'validityCountry' => $item['documents'][0]['validity_country'],
                        'nationality' => $item['documents'][0]['nationality'],
                        'holder' => $item['documents'][0]['holder'],
                    ],
                ],
            ];
        })->toArray();

        $remarks = [
            'general' => [
                [
                    'subType' => 'GENERAL_MISCELLANEOUS',
                    'text' => $this->flightSetting->company_name,
                ],
            ],
        ];

        $ticketAgreement = [
            'option' => $this->flightSetting->flight_ticket_option,
            'delay' => strval($this->flightSetting->delay).'D',
        ];

        $contacts = [[
            'addresseeName' => [
                'firstName' => $this->flightSetting->addressee_first_name,
                'lastName' => $this->flightSetting->addressee_last_name,
            ],
            'companyName' => $this->flightSetting->company_name,
            'purpose' => 'STANDARD',
            'phones' => [[
                'deviceType' => 'MOBILE',
                'countryCallingCode' => $this->flightSetting->country_calling_code,
                'number' => $this->flightSetting->company_phone,

            ]],
            'emailAddress' => $this->flightSetting->email_address_for_amadeus,
            'address' => [
                'lines' => [$this->flightSetting->company_address],
                'postalCode' => $this->flightSetting->postal_code,
                'cityName' => $this->flightSetting->city_name,
                'countryCode' => $this->flightSetting->country_code,
            ],
        ]];

        return [
            'travelers' => $travelers,
            'remarks' => $remarks,
            'ticketing' => $ticketAgreement,
            'contacts' => $contacts,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getAirlines(): array
    {
        try {
            $data = @file_get_contents(storage_path('airlines.json')); //@phpstan-ignore-next-line
            $data = json_decode($data);
        } catch (\Throwable $th) {
            logger($th);
            throw new CustomException('Unable to fetch airlines');
        }

        return $data;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,mixed>  $data
     */
    private function createPendingFlightBooking(FlightOffers $rawOffer, array $payload, array $data): BookFlight
    {
        $charge = $this->generalSetting->flight_service_charge;
        $flightPrice = $rawOffer['price']['grandTotal'] + $charge;

        return $this->bookFlight->create([
            'flight_cost' => $flightPrice,
            'offer_id' => $rawOffer->id,
            'locations' => $rawOffer->locations,
            'search_meta' => $rawOffer->search_meta,
            'request_body' => $payload,
            'flight_order_ref' => getRandomReference('ZPP'),
            'user_id' => auth()->id(),
            'status' => FlightStatus::PENDING->value,
            'payment_status' => TransactionStatus::PENDING->value,
            'carrier' => $data['carrier'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function retryBookingForFailedPayment(): array
    {
        $data = request()->validate([
            'booking_id' => 'bail|required',
            'payment_reference' => 'bail|required',
        ]);

        $flight = BookFlight::whereId($data['booking_id'])->wherePaymentStatus(TransactionStatus::FAILED->value)->first();
        $trxn = Transaction::whereReference($data['payment_reference'])->first();
        throw_if(blank($flight) || blank($trxn), CustomException::class, 'No record found');

        // should they pass previous processed data
        throw_if($flight->id == $trxn->book_flight_id, CustomException::class, 'Payment and booking already exist');
        throw_if($flight->payment_status != TransactionStatus::FAILED->value, 'You can only retry boooking for a failed payment');
        throw_if(now()->diffInMinutes(Carbon::parse($flight->updated_at)) > 60, CustomException::class, 'Offer can\'t be retried, please start afresh');
        $verifiedTrxn = $this->verifyFlightPayment($trxn->offer_id, $trxn->reference);
        if ($verifiedTrxn->status == TransactionStatus::PENDING->value) {
            $trxn->update(['book_flight_id' => $flight->id, 'offer_id' => $flight->offer_id, 'temp_booking_ref' => $flight->id]);

            throw new CustomException('Payment processing, please try again in few moments');
        }

        if ($verifiedTrxn->status == TransactionStatus::FAILED->value) {
            $trxn->update(['book_flight_id' => $flight->id, 'offer_id' => $flight->offer_id, 'temp_booking_ref' => null]);
            $flight->update(['payment_status' => TransactionStatus::FAILED->value]);
            throw new CustomException('Booking payment failed');
        }

        $this->requestForAmadeusBooking(
            payload: $flight->request_body, //@phpstan-ignore-line
            pendingBooking: $flight,
            rawOffer: null,
            trxn: $trxn,
            data: ['offer_id' => $flight->offer_id]
        );

        return ['booking_id' => $flight->id];
    }

    /**
     * @return array<string, mixed>
     */
    public function retryBookingForPendingPayment(): array
    {
        $data = request()->validate([
            'booking_id' => 'bail|required',
        ]);

        $flight = BookFlight::whereId($data['booking_id'])->wherePaymentStatus(TransactionStatus::PENDING->value)->first();
        $trxn = Transaction::whereBookFlightId($flight?->id)->whereTempBookingRef($flight->id)->whereStatus(TransactionStatus::PENDING->value)->first(); //@phpstan-ignore-line
        throw_if(blank($flight) || blank($trxn), CustomException::class, 'No record found');
        throw_if($flight->payment_status != TransactionStatus::PENDING->value, 'You can only retry boooking for a pending payment');
        throw_if(now()->diffInMinutes(Carbon::parse($flight->updated_at)) > 60, CustomException::class, 'Offer can\'t be retried, please start afresh');
        $verifiedTrxn = $this->verifyFlightPayment($trxn->offer_id, $trxn->reference);
        if ($verifiedTrxn->status == TransactionStatus::PENDING->value) {
            $trxn->update(['book_flight_id' => $flight->id, 'offer_id' => $flight->offer_id, 'temp_booking_ref' => $flight->id]);
            $flight->update(['payment_status' => TransactionStatus::PENDING->value]);
            throw new CustomException('Payment processing, please try again in few moments');
        }

        if ($verifiedTrxn->status == TransactionStatus::FAILED->value) {
            $trxn->update(['book_flight_id' => $flight->id, 'offer_id' => $flight->offer_id, 'temp_booking_ref' => null]);
            $flight->update(['payment_status' => TransactionStatus::FAILED->value]);
            throw new CustomException('Booking payment failed');
        }

        $this->requestForAmadeusBooking(
            payload: $flight->request_body, //@phpstan-ignore-line
            pendingBooking: $flight,
            rawOffer: null,
            trxn: $trxn,
            data: ['offer_id' => $flight->offer_id]
        );

        return ['booking_id' => $flight->id];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    private function requestForAmadeusBooking(array $payload, array $data, BookFlight $pendingBooking, ?FlightOffers $rawOffer, Transaction $trxn): void
    {
        $resp = $this->amadeus->bookFlight($payload);
        try {
            DB::beginTransaction();
            if (array_key_exists('data', $resp)) {
                $pendingBooking->update([
                    'flight_order_ref' => $resp['data']['id'], 'offer_id' => $data['offer_id'],
                    'flight_cost' => $resp['data']['flightOffers'][0]['price']['total'], 'flight_offers' => $resp['data']['flightOffers'],
                    'passengers' => $resp['data']['travelers'], 'remarks' => $resp['data']['remarks'],
                    'ticketing_agreement' => $resp['data']['ticketingAgreement'], 'dictionaries' => $resp['dictionaries'],
                    'raw_response' => $resp['data'], 'status' => FlightStatus::COMPLETED->value, 'payment_status' => $trxn->status,
                ]);
                //delete offers that's not a deal
                if (blank($rawOffer?->expires_at)) {
                    @$rawOffer?->delete();
                }
                $trxn->update(['book_flight_id' => $pendingBooking->id, 'temp_booking_ref' => null]);
            } else {
                // $pendingBooking->update(['status' => FlightStatus::FAILED->value]);
                throw new CustomException('Unable to update record');
            }
            DB::commit();
        } catch (Throwable $th) {
            DB::commit();
            logger($th);
            throw new CustomException($th->getMessage());
        }
    }
}
