<?php

namespace Database\Factories;

use App\Enums\Status\AdminStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
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
            'phone_code' => '+234',
            'phone' => fake()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => AdminStatus::ACTIVE,
        ];
    }

    public function inActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdminStatus::RESTRICTED,
        ]);
    }

    public function withOtp(string $otp = '', bool $expired = false): static
    {
        if (! $otp) {
            $otp = app()->environment('production') ? (string) rand(1000, 9999) : '1234';
        }

        $expiry = $expired ? now()->subHour() : now()->addHour();

        return $this->state(fn (array $attributes) => [
            'otp' => bcrypt($otp),
            'otp_expiry' => $expiry,
        ]);
    }

    public function with2fa(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled_at' => now(),
        ]);
    }
}
