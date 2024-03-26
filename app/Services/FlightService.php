<?php

namespace App\Services;

use App\Comms\Amadeus;
use App\Exceptions\CustomException;
use App\Models\City;
use App\Models\FlightOffers;
use Illuminate\Support\Collection;

class FlightService extends BaseService
{
    public function __construct(
        protected Amadeus $amadeus,
        protected City $city,
        protected FlightOffers $flightOffers
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
        $offers = $this->amadeus->searchOffers($payload);
        if (blank($offers)) {
            throw new CustomException('Search doesn\'t bring results');
        }

        foreach ($offers as $offer) {

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
        return $this->paginate(
            query: $this->city,
            columns: ['id', 'city_name', 'city_code']
        );
    }
}
