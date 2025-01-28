<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlightDeals extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'itineraries' => 'json',
        'locations' => 'json',
        'price' => 'json',   // Ensure price is cast as JSON
        'pricing_option' => 'json',
        'traveler_pricing' => 'json',
        'raw_offer_response' => 'json',
        'dictionaries' => 'json',
        'search_meta' => 'json',
    ];
}
