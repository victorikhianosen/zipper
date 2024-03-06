<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    public array $validPayload = [
        'first_name' => 'john',
        'last_name' => 'doe',
        'email' => 'johndoe@gmail.com',
        'phone' => '08144259341',
        'password' => 'password',
    ];

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * @dataProvider failValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsValidation($inputData): void
    {
        $response = $this->postJson('/register', $inputData);

        $response->assertStatus(422);
    }

    public function testItRegistersANewUserAndSendsVerificationEmail(): void
    {
        $response = $this->postJson('/register', $this->validPayload);

        $response->assertStatus(200);

        Notification::assertSentTo(
            [User::where('email', $this->validPayload['email'])->first()],
            EmailVerificationRequest::class
        );
    }

    public function testItReturnsTheUserAndAuthToken(): void
    {
        $response = $this->postJson('/register', $this->validPayload);

        $response->assertSee(['user', 'token']);
    }

    public function testItThrowsEmailIsAlreadyTaken(): void
    {
        $this->postJson('/register', $this->validPayload);
        $response = $this->postJson('/register', $this->validPayload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function testItGeneratesUserAccounts(): void
    {
        $response = $this->postJson('/register', $this->validPayload);

        $userId = $response->json()['data']['user']['id'];
        $user = User::findOrFail($userId);

        $this->assertInstanceOf(User::class, $user);
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
            'no_first_name' => [
                [
                    'first_name' => 'Doe',
                    'email' => 'johndoe@gmail.com',
                    'password' => 'password',
                ],
            ],
            'no_last_name' => [
                [
                    'last_name' => 'Doe',
                    'email' => 'johndoe@gmail.com',
                    'password' => 'password',
                ],
            ],
            'no_email' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'password',
                ],
            ],
            'invalid_email' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'johndoe',
                    'password' => 'password',
                ],
            ],
            'no_password' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'johndoe@gmail.com',
                ],
            ],
            'short_password' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'johndoe@gmail.com',
                    'password' => 'pass',
                ],
            ],
        ];
    }
}
