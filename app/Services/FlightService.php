<?php

namespace App\Services;

use App\Comms\Amadeus;
use App\Exceptions\CustomException;
use App\Http\Requests\FlightBookingRequest;
use App\Models\BookFlight;
use App\Models\City;
use App\Models\FlightOffers;
use Illuminate\Support\Collection;

class FlightService extends BaseService
{
    public function __construct(
        protected Amadeus $amadeus,
        protected City $city,
        protected FlightOffers $flightOffers,
        protected BookFlight $bookFlight
    ) {
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
        $filter = request()->query('filter'); //@phpstan-ignore-next-line
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
        $price = $amadeusValidatedDetail['data']['flightOffers'][0]['price']; //@phpstan-ignore-next-line
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
        $resp = $this->amadeus->bookFlight($payload);
        if (array_key_exists('data', $resp)) {
            $data = $this->bookFlight->create([
                'flight_order_ref' => $resp['data']['id'],
                'locations' => $rawOffer->locations,
                'flight_cost' => $resp['data']['flightOffers'][0]['price']['total'], 'flight_offers' => $resp['data']['flightOffers'],
                'passengers' => $resp['data']['travelers'], 'remarks' => $resp['data']['remarks'],
                'ticketing_agreement' => $resp['data']['ticketingAgreement'], 'dictionaries' => $resp['dictionaries'],
                'raw_response' => $resp['data'], 'search_meta' => $rawOffer->search_meta,
            ]);
        } else {
            throw new CustomException('An error occured');
        }

        return $data;
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
                    'phones' => [['deviceType' => $item['contact']['phones'][0]['device_type'],
                        'countryCallingCode' => $item['contact']['phones'][0]['country_calling_code'],
                        'number' => $item['contact']['phones'][0]['number']]],
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
                    'text' => 'ZIPPA ONLINE BOOKING',
                ],
            ],
        ];

        $ticketAgreement = [
            'option' => 'DELAY_TO_CANCEL',
            'delay' => '6D',
        ];

        $contacts = [[
            'addresseeName' => [
                'firstName' => 'ANDY',
                'lastName' => 'ROGERS',
            ],
            'companyName' => 'ZIPPA',
            'purpose' => 'STANDARD',
            'phones' => [[
                'deviceType' => 'MOBILE',
                'countryCallingCode' => '33',
                'number' => '480080076',

            ]],
            'emailAddress' => 'jsupport@increibleviajes.es',
            'address' => [
                'lines' => ['Calle Prado, 16'],
                'postalCode' => '28014',
                'cityName' => 'Madrid',
                'countryCode' => 'ES',
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
}
