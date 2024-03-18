<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public static string $email = 'johndoe@gmail.com';

    public static string $otp = '1234';

    public static string $invalidEmail = 'goke@gmail.com';

    public static string $invalidOtp = '4321';

    public function testItFailsToSend2faMailDueTo2faNotEnabled(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/resend-2fa-email', ['email' => $user->email]);
        $response->assertStatus(400);

    }

    public function testItFailsToSend2faMailDueToTooManyRequest(): void
    {
        $user = User::factory()->create();
        $tries = 0;
        while ($tries <= 3) {
            $this->postJson('/resend-2fa-email', ['email' => $user->email]);
            $tries++;
        }
        $response = $this->postJson('/resend-2fa-email', ['email' => $user->email]);
        $response->assertStatus(429);
    }

    /**
     * @dataProvider failVerifyPasswordResetTokenValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToSend2faMailDueToValidationError(array $inputData): void
    {
        $response = $this->postJson('/resend-2fa-email', $inputData);
        $response->assertStatus(422);
    }

    public function testItSends2faMail(): void
    {
        Notification::fake();

        $user = User::factory()->with2fa()->create();

        $response = $this->postJson('/resend-2fa-email', ['email' => $user->email]);

        $response->assertStatus(200);
        $response->assertSee("A 2fa otp has been resent to $user->email");

        Notification::assertSentTo($user, TwoFactorRequest::class);
    }

    public function testItFailsToVerify2faDueToInvalidOtp(): void
    {
        $user = User::factory()->withOtp('1023')->with2fa()->create();
        $response = $this->postJson('/verify-2fa-token', ['email' => $user->email, 'otp' => '1025']);
        $response->assertStatus(400);
        $response->assertSee('Invalid OTP entered');
    }

    public function testItFailsToVerify2faDueToOtpExpired(): void
    {
        $user = User::factory()->withOtp('1023', true)->with2fa()->create();
        $response = $this->postJson('/verify-2fa-token', ['email' => $user->email, 'otp' => '1023']);
        $response->assertStatus(400);
        $response->assertSee('Expired OTP entered');
    }

    public function testItVerifies2faOtp(): void
    {
        $user = User::factory()->withOtp('1055')->with2fa()->create();
        $response = $this->postJson('/verify-2fa-token', ['email' => $user->email, 'otp' => '1055']);
        $response->assertStatus(200);
        $this->assertNull($user->refresh()->verification_otp);
        $this->assertNull($user->refresh()->verification_otp_expiry);
    }

    public function testItSends2faMailAfterLogin(): void
    {
        Notification::fake();

        $user = User::factory()->with2fa()->create();

        $response = $this->postJson('/login/password', ['email' => $user->email, 'password' => 'password']);

        $response->assertStatus(200);
        $this->assertEquals($response->json()['message'], "A 2fa otp has been sent to $user->email");

        Notification::assertSentTo($user, TwoFactorRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public static function failVerifyPasswordResetTokenValidationInputs(): array
    {
        return [
            'no_data' => [
                [],
            ],
            'invalid_email' => [
                [
                    'email' => static::$invalidEmail,
                    'otp' => static::$otp,
                ],
            ],
            'less_than_4_digits_otp' => [
                [
                    'email' => static::$email,
                    'otp' => '1',
                ],
                [
                    'email' => static::$email,
                    'otp' => '103',
                ],
                [
                    'email' => static::$email,
                    'otp' => '103',
                ],
            ],
            'more_than_4_digits_otp' => [
                [
                    'email' => static::$email,
                    'otp' => '10234',
                ],
                [
                    'email' => static::$email,
                    'otp' => '102343',
                ],
                [
                    'email' => static::$email,
                    'otp' => '1023436',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function failPasswordResetValidationInputs(): array
    {
        return [
            'no_data' => [
                [],
            ],
            'invalid_email' => [
                [
                    'email' => static::$invalidEmail,
                    'otp' => static::$otp,
                ],
            ],
            'less_than_4_digits_otp' => [
                [
                    'email' => static::$email,
                    'otp' => '1',
                ],
                [
                    'email' => static::$email,
                    'otp' => '103',
                ],
                [
                    'email' => static::$email,
                    'otp' => '103',
                ],
            ],
            'more_than_4_digits_otp' => [
                [
                    'email' => static::$email,
                    'otp' => '10234',
                ],
                [
                    'email' => static::$email,
                    'otp' => '102343',
                ],
                [
                    'email' => static::$email,
                    'otp' => '1023436',
                ],
            ],
        ];
    }
}
