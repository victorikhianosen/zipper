<?php

namespace Database\Factories;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notifiable_type' => User::class,
            'type' => AppNotification::class,
            'data' => [
                'title' => fake()->sentence(),
                'body' => fake()->sentences(4),
            ],
        ];
    }

    /**
     * Indicate that the notification should be read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
