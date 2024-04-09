<?php

namespace App\Models;

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
    ];

    protected $guarded = [];
}
