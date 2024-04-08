<?php

namespace App\Http\Resources;

use Carbon\Carbon;

class DateResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static function format(string $date): array
    {

        $data = [
            'one' => Carbon::parse($date)->toDateString(),
            'two' => Carbon::parse($date)->toDateTimeString(),
            'three' => Carbon::parse($date)->toDayDateTimeString(),
            'four' => Carbon::parse($date)->diffForHumans(),
        ];

        return $data;
    }
}
