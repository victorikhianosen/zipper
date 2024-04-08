<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $itineraries = $data['flight_offers'][0]['itineraries']; // @phpstan-ignore-line
        $search_meta = $data['search_meta']; // @phpstan-ignore-line
        $segmentLoopArray = []; // @phpstan-ignore-next-line
        $travelers = collect($data['passengers'])
            ->map(function ($item) {
                return [
                    'last_name' => $item['name']['firstName'],
                    'first_name' => $item['name']['lastName'],
                    'email' => $item['contact']['emailAddress'],
                    'phone' => $item['contact']['phones'],
                ];
            })
            ->toArray(); // @phpstan-ignore-next-line
        $pricing = collect($data['flight_offers'][0]['travelerPricings'])
            ->map(function ($item) {
                return [
                    'traveler_id' => $item['travelerId'],
                    'traveler_type' => $item['travelerType'],
                    'currency' => $item['price']['currency'],
                    'total' => $item['price']['total'],
                ];
            })
            ->toArray();
        $details = [
            'tracking_status' => $data['tracking_status'] ?? null,
            'booking_status' => $data['status'] ?? null,
            'passenger_count' => $search_meta['no_of_children'] + $search_meta['no_of_adult'] + $search_meta['no_of_infant'],
            'queries' => $search_meta,
            'locations' => $data['locations'], // @phpstan-ignore-line
            'passengers' => $travelers,
            'pricing' => $pricing,
        ];

        foreach ($itineraries as $key => $val) {
            $itTrip = $key == 0 ? 'going' : 'coming';
            foreach ($val['segments'] as $sg) {
                $segmentLoopArray[] = ['depart_from' => $sg['departure']['iataCode'],
                    'departing_terminal' => $sg['departure']['terminal'] ?? null,
                    'depart_at' => DateResource::format($sg['departure']['at']),
                    'arrive_in' => $sg['arrival']['iataCode'],
                    'arrival_terminal' => $sg['arrival']['terminal'] ?? null,
                    'arrive_at' => DateResource::format($sg['arrival']['at']),
                    'itinerary_duration' => $sg['duration'],
                    'airline' => getAirlineMeta($sg['carrierCode']),
                ];
            }
            $details['itineraries'][$itTrip] = $segmentLoopArray; // @phpstan-ignore-line
            unset($segmentLoopArray);
        }

        return $details;

    }
}
