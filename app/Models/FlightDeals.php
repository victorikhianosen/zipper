<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlightDeals extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    // Specify the correct table name
    protected $table = 'flight_deals';  // Ensure this is correct

    // If you're using UUIDs or a non-incrementing ID
    public $incrementing = false;
    protected $keyType = 'string';

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }
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
