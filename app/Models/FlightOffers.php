<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightOffers extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'itineraries' => 'json', 'locations' => 'json',
        'price' => 'json', 'pricing_option' => 'json',
        'traveler_pricing' => 'json', 'raw_offer_response' => 'json',
        'dictionaries' => 'json', 'search_meta' => 'json',
    ];
}
