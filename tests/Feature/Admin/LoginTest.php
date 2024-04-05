<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
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
        'first_name' => 'Super',
        'last_name' => 'Admin',
        'email' => 'admin@zippa.com',
        'password' => 'password',
    ];

    /**
     * @var array<string, string>
     */
    public array $validPayload = [
        'email' => 'admin@zippa.com',
        'password' => 'password',
    ];

    /**
     * @dataProvider failValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsValidation(array $inputData): void
    {
        Admin::factory()->create($this->user);

        $response = $this->postJson('/admin/login', $inputData);

        $response->assertStatus(422);
    }

    public function testItReturnsTheAdminAndAuthToken(): void
    {
        Admin::factory()->create($this->user);

        $response = $this->postJson('/admin/login', $this->validPayload);

        $response->assertSee(['admin', 'token']);
    }

    public function testItLogsAdminOut(): void
    {
        $user = Admin::factory()->create($this->user);

        Sanctum::actingAs($user, ['*'], 'admin');

        $response = $this->postJson('/admin/logout');

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
