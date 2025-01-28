<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\City;
use App\Comms\Amadeus;
use App\Models\FlightDeals;
use App\Models\FlightOffers;
use App\Models\FlightSetting;
use Illuminate\Support\Collection;
use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Http\Requests\FlightSettingRequest;

class FlightSettingService extends BaseService
{
    public function __construct(
        protected FlightSetting $setting,
        protected Amadeus $amadeus,
        protected City $city,
        protected FlightOffers $flightOffers,
    ) {
    }

    public function create(FlightSettingRequest $request): Model
    {
        $numRows = $this->setting->count();
        if ($numRows > 0) {
            throw new CustomException('Flight-Settings already created, please modify instead');
        }

        return $this->setting->create($request->validated());
    }

    public function edit(FlightSettingRequest $request): void
    {
        $data = $request->validated();
        $this->setting->first()?->update($data);
    }

    public function all(): ?Model
    {
        return $this->setting->first();
    }

    // public function createFlightDeals(): void
    // {
    //     $payload = request()->validate([
    //         'origin' => 'bail|required|string',
    //         'destination' => 'bail|required|string',
    //         'departureDate' => 'bail|date|required',
    //         'oneWay' => 'bail|boolean',
    //         'duration' => 'bail|string',
    //         'nonStop' => 'bail | boolean',
    //         // 'maxPrice' => 'bail|integer'
    //     ]);
    //     $payload['viewBy'] = 'DATE';
    //     if ($payload['oneWay'] == true) {
    //         unset($payload['duration']);
    //     }
    //     $deals = $this->amadeus->flightDeals($payload);
    // }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed,mixed> | \Illuminate\Support\Collection
     */




    public function getOffers(array $data): array|Collection
    {
        $search_id = $data['search_id'];
        $fromCity = $this->city->where('city_code', $data['from'])->first()?->city_name;
        $toCity = $this->city->where('city_code', $data['to'])->first()?->city_name;

        $payload = [
            'originLocationCode' => $data['from'],
            'destinationLocationCode' => $data['to'],
            'departureDate' => $data['departure_date'],
            'adults' => $data['no_of_adult'],
            'children' => $data['no_of_children'],
            'infants' => $data['no_of_infant'],
            'travelClass' => $data['flight_class'],
            'nonStop' => $data['stop_over'],
            'currencyCode' => 'NGN',
            'max' => 2,
        ];
        if ($data['trip_type'] === 'round') {
            $payload['returnDate'] = $data['arrival_date'];
        }

        $offers = $this->amadeus->searchOffers($payload);

        if (blank($offers) || blank($offers['data'])) {
            throw new CustomException('Search doesn\'t bring results');
        }

        // Prevent duplicate records
        $existingOffer = $this->flightOffers->where('search_id', $search_id)->first();
        if ($existingOffer) {
            return $this->flightOffers->where('search_id', $search_id)->get();
        }

        // Save the first offer only
        $firstOffer = $offers['data'][0];

        $offer = $this->flightOffers->create([
            'search_id' => $search_id,
            'itineraries' => $firstOffer['itineraries'],
            'price' => $firstOffer['price'],
            'pricing_option' => $firstOffer['pricingOptions'],
            'traveler_pricing' => $firstOffer['travelerPricings'],
            'raw_offer_response' => $firstOffer,
            'locations' => [
                'from_code' => $data['from'],
                'from_city' => $fromCity,
                'to_code' => $data['to'],
                'to_city' => $toCity,
            ],
            'search_meta' => $data,
            'dictionaries' => $offers['dictionaries'],
            'expires_at' => Carbon::parse($data['expires_at']),
            'image_link' => $data['image_url'],
        ]);

        // Update the price field
        $price_array = $firstOffer['price'];
        $price_array['total'] = $data['price'];
        $price_array['grandTotal'] = $data['price'];
        $offer->update(['price' => $price_array]);

        // Retrieve and return saved offers from the database
        $fromDb = $this->flightOffers->where('search_id', $search_id)->get();

        return $fromDb;
    }





    // public function getOffers(array $data): array|Collection
    // {
    //     $search_id = $data['search_id'];
    //     $fromCity = $this->city->where('city_code', $data['from'])->first()?->city_name;
    //     $toCity = $this->city->where('city_code', $data['to'])->first()?->city_name;

    //     $payload = [
    //         'originLocationCode' => $data['from'],
    //         'destinationLocationCode' => $data['to'],
    //         'departureDate' => $data['departure_date'],
    //         'adults' => $data['no_of_adult'],
    //         'children' => $data['no_of_children'],
    //         'infants' => $data['no_of_infant'],
    //         'travelClass' => $data['flight_class'],
    //         'nonStop' => $data['stop_over'],
    //         'currencyCode' => 'NGN',
    //         'max' => 2,
    //     ];
    //     if ($data['trip_type'] === 'round') {
    //         $payload['returnDate'] = $data['arrival_date'];
    //     }

    //     $offers = $this->amadeus->searchOffers($payload);

    //     if (blank($offers) || blank($offers['data'])) {
    //         throw new CustomException('Search doesn\'t bring results');
    //     }

    //     // Prevent duplicate records
    //     $existingOffer = $this->flightOffers->where('search_id', $search_id)->first();
    //     if ($existingOffer) {
    //         return $this->flightOffers->where('search_id', $search_id)->get();
    //     }

    //     // Save the first offer only
    //     $firstOffer = $offers['data'][0];

    //     $offer = $this->flightOffers->create([
    //         'search_id' => $search_id,
    //         'itineraries' => $firstOffer['itineraries'],
    //         'price' => $firstOffer['price'],
    //         'pricing_option' => $firstOffer['pricingOptions'],
    //         'traveler_pricing' => $firstOffer['travelerPricings'],
    //         'raw_offer_response' => $firstOffer,
    //         'locations' => [
    //             'from_code' => $data['from'],
    //             'from_city' => $fromCity,
    //             'to_code' => $data['to'],
    //             'to_city' => $toCity,
    //         ],
    //         'search_meta' => $data,
    //         'dictionaries' => $offers['dictionaries'],
    //         'expires_at' => Carbon::parse($data['expires_at']),
    //         'image_link' => $data['image_url'],
    //     ]);

    //     // Update the price field
    //     $price_array = $firstOffer['price'];
    //     $price_array['total'] = $data['price'];
    //     $price_array['grandTotal'] = $data['price'];
    //     $offer->update(['price' => $price_array]);

    //     // Retrieve and return saved offers from the database
    //     $fromDb = $this->flightOffers->where('search_id', $search_id)->get();

    //     return $fromDb;
    // }


    /**
     * @return array<mixed,mixed> | \Illuminate\Support\Collection
     */
    public function getFlightDeals(): array|Collection
    {
        $activeFlightDeals = FlightOffers::whereNotNull('expires_at')->where('expires_at', '>', now());

        return $this->paginate($activeFlightDeals);
    }
}
