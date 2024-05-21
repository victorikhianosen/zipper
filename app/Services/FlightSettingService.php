<?php

namespace App\Services;

use App\Comms\Amadeus;
use App\Exceptions\CustomException;
use App\Http\Requests\FlightSettingRequest;
use App\Models\City;
use App\Models\FlightOffers;
use App\Models\FlightSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

            $offer = $this->flightOffers->create([
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
                'expires_at' => Carbon::parse($data['expires_at']),
            ]);
            $price_array = $offer['price'];
            $price_array['total'] = $data['price'];
            $price_array['grandTotal'] = $data['price'];
            $offer->update(['price' => $price_array]);
        }

        $fromDb = $this->flightOffers->where('search_id', $search_id)->get();

        return $fromDb;
    }
}
