<?php

namespace App\Models;

use App\Enums\Gateway;
use App\Enums\ServiceType;
use App\Enums\TransactionCat;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;

    protected $hidden = ['created_at'];

    protected $casts = [
        'meta' => 'json',
        'raw_response' => 'json',
        'service_type' => ServiceType::class,
        'transaction_type' => TransactionType::class,
        'category' => TransactionCat::class,
        'gateway' => Gateway::class,
        'status' => TransactionStatus::class,

    ];

    protected $guarded = [];
}
