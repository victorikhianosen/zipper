<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookFlight extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected $table = 'book_flights';

    protected $casts = [
        'flight_offers' => 'json',
        'passengers' => 'json',
        'remarks' => 'json',
        'ticketing_agreement' => 'json',
        'dictionaries' => 'json',
        'raw_response' => 'json',
        'search_meta' => 'json',
        'locations' => 'json',
        'request_body' => 'json',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
