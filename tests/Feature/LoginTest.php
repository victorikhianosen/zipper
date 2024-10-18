<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    public array $user = [
        'first_name' => 'john',
        'last_name' => 'doe',
        'email' => 'johndoe@gmail.com',
        'password' => 'password',
    ];

    /**
     * @var array<string, string>
     */
    public array $validPayload = [
        'email' => 'johndoe@gmail.com',
        'password' => 'password',
    ];

    /**
     * @dataProvider failValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsValidation(array $inputData): void
    {
        User::factory()->create($this->user);

        $response = $this->postJson('/login/password', $inputData);

        $response->assertStatus(422);
    }

    public function testItReturnsTheUserAndAuthToken(): void
    {
        User::factory()->create($this->user);

        $response = $this->postJson('/login/password', $this->validPayload);

        $response->assertSee(['user', 'token']);
    }

    public function testItLogsUserOut(): void
    {
        $user = User::factory()->create($this->user);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/logout');

        $response->assertStatus(200);
    }

    /**
     * @return array<string, mixed>
     */
    public static function failValidationInputs(): array
    {
        return [
            'no_data' => [
                [],
            ],
            'no_email' => [
                [
                    'password' => 'password',
                ],
            ],
            'no_password' => [
                [
                    'email' => 'johndoe@gmail.com',
                ],
            ],
            'invalid_email' => [
                [
                    'email' => 'johndoe@yahoo',
                    'password' => 'password',
                ],
            ],
            'invalid_password' => [
                [
                    'email' => 'johndoe@gmail.com',
                    'password' => 'invalid_password',
                ],
            ],
        ];
    }
}
