<?php

namespace App\Models;

use App\Enums\Gateway;
use App\Enums\ServiceType;
use App\Enums\TransactionCat;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $hidden = ['created_at', 'raw_response'];

    protected $casts = [
        'meta' => 'json',
        'raw_response' => 'json',
        'service_type' => ServiceType::class,
        'transaction_type' => TransactionType::class,
        'category' => TransactionCat::class,
        'gateway' => Gateway::class,
        // 'status' => TransactionStatus::class,

    ];

    protected $guarded = [];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(FlightOffers::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(BookFlight::class, 'book_flight_id');
    }
}
