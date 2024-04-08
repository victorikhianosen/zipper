<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $itineraries = $data['itineraries']; // @phpstan-ignore-line
        $aircrafts = $data['dictionaries']['aircraft']; // @phpstan-ignore-line
        $segmentLoopArray = [];
        $details = [
            'id' => $data['id'], // @phpstan-ignore-line
            'total' => $data['price']['currency'].''.$data['price']['total'], // @phpstan-ignore-line
            'departure_from' => $data['locations']['from_city'].'('.$data['locations']['from_code'].')', // @phpstan-ignore-line
            'arrival_to' => $data['locations']['to_city'].'('.$data['locations']['to_code'].')', // @phpstan-ignore-line
            'trip_type' => $data['raw_offer_response']['oneWay'] == false ? 'Round-Trip' : 'One-Way', // @phpstan-ignore-line
            'itineraries' => [],
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
                    'aircraft' => $aircrafts[$sg['aircraft']['code']], ];
            }
            $details['itineraries'][$itTrip] = $segmentLoopArray; // @phpstan-ignore-line
            unset($segmentLoopArray);
        }
        $details['aircraft'] = $aircrafts;
        $details['carrier'] = $data['dictionaries']['carriers']; //@phpstan-ignore-line

        return $details;
    }
}
