<?php

namespace Database\Factories;

use App\Enums\Status\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => UserStatus::ACTIVE,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::RESTRICTED,
        ]);
    }

    public function pushNotificationEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_tokens' => [Str::random(128)],
        ]);
    }

    public function withOtp(string $otp = '', bool $expired = false): static
    {
        if (! $otp) {
            $otp = app()->environment('production') ? (string) rand(100000, 999999) : '1234';
        }

        $expiry = $expired ? now()->subHour() : now()->addHour();

        return $this->state(fn (array $attributes) => [
            'verification_otp' => bcrypt($otp),
            'verification_otp_expiry' => $expiry,
        ]);
    }

    public function with2fa(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled_at' => now(),
        ]);
    }
}
