<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        $segments = $data['itineraries'][0]['segments']; // @phpstan-ignore-line
        $details = [
            'id' => $data['id'], // @phpstan-ignore-line
            'total' => $data['price']['currency'].''.$data['price']['total'], // @phpstan-ignore-line
            'departure_from' => $data['locations']['from_city'].'('.$data['locations']['from_code'].')', // @phpstan-ignore-line
            'arrival_to' => $data['locations']['to_city'].'('.$data['locations']['to_code'].')', // @phpstan-ignore-line
            'trip_type' => $data['raw_offer_response']['oneWay'] == false ? 'Round-Trip' : 'One-Way', // @phpstan-ignore-line
            'departure_time' => Carbon::parse($segments[0]['departure']['at'])->toDateTimeString(),
        ];

        // if there will be a stop over
        if (count($segments) > 1) {
            $details['stop_in'] = $segments[0]['arrival']['iataCode'];
            $details['stop_at'] = Carbon::parse($segments[0]['arrival']['at'])->toDateTimeString();
            $details['arrival_time'] = Carbon::parse($segments[1]['arrival']['at'])->toDateTimeString();
        } else {
            $details['arrival_time'] = Carbon::parse($segments[0]['arrival']['at'])->toDateTimeString();
        }

        $interval = Carbon::parse($details['departure_time'])->diff($details['arrival_time']);
        $days = $interval->d;
        $hrs = $interval->h;
        $minute = $interval->i;
        $details['aircraft'] = $data['dictionaries']['aircraft']; //@phpstan-ignore-line
        $details['carrier'] = $data['dictionaries']['carriers']; //@phpstan-ignore-line
        $details['interval'] = $days > 0 ? $days.'d '.$hrs.'h '.$minute.'m' : $hrs.'h '.$minute.'m';
        $details['duration'] = $segments[0]['duration'];

        return $details;
    }
}
