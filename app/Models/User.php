<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\AuthType;
use App\Enums\Gender;
use App\Enums\Status\UserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_code',
        'username',
        'gender',
        'auth_type',
        'ref_code',
        'password',
        'verification_otp',
        'verification_otp_expiry',
        'email_verified_at',
        'two_factor_enabled_at',
        'login_biometric_activated_at',
        'device_tokens',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_tokens',
        'verification_otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'auth_type' => AuthType::class,
        'gender' => Gender::class,
        'status' => UserStatus::class,
        'password' => 'hashed',
        'device_tokens' => 'array',
        'email_verified_at' => 'datetime',
        'two_factor_enabled_at' => 'datetime',
        'login_biometric_activated_at' => 'datetime',
        'verification_otp_expiry' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            $user->update(['email' => '['.now().'] '.$user->email]);
        });
    }

    public static function generateRefCode(): string
    {
        do {
            $ref = Str::upper(Str::random(8));
        } while (static::query()->where('ref_code', $ref)->exists());

        return $ref;
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function flights(): HasMany
    {
        return $this->hasMany(BookFlight::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function getPointAttribute(): float
    {
        return $this->wallet()->first()->point_bonus; //@phpstan-ignore-line
    }

    public function depositPoint(float $amount): void
    {
        $this->wallet()->increment('point_bonus', $amount);
    }
}
