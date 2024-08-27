<?php

namespace App\Models;

use App\Enums\Status\AdminStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuids;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_expiry',
        'fcm_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'two_factor_enabled_at' => 'datetime',
        'otp_expiry' => 'datetime',
        'password' => 'hashed',
        'otp' => 'hashed',
        'status' => AdminStatus::class,
    ];

    public static function scopeSearch(mixed $query, string $search): mixed
    {
        return $query->where('first_name', 'LIKE', "%$search")
            ->orWhere('last_name', 'LIKE', "%$search")
            ->orWhere('email', 'LIKE', "%$search")
            ->orWhere('phone', 'LIKE', "%$search");
    }
}
