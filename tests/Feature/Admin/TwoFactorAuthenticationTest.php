<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Notifications\TwoFactorRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public static string $email = 'admin@zippa.com';

    public static string $otp = '1234';

    public static string $invalidEmail = 'goke@gmail.com';

    public static string $invalidOtp = '4321';

    public function testItFailsToSend2faMailDueTo2faNotEnabled(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->postJson('/admin/resend-2fa-email', ['email' => $admin->email]);
        $response->assertStatus(400);

    }

    public function testItFailsToSend2faMailDueToTooManyRequest(): void
    {
        $admin = Admin::factory()->create();
        $tries = 0;
        while ($tries <= 3) {
            $this->postJson('/admin/resend-2fa-email', ['email' => $admin->email]);
            $tries++;
        }
        $response = $this->postJson('/admin/resend-2fa-email', ['email' => $admin->email]);
        $response->assertStatus(429);
    }

    /**
     * @dataProvider failVerifyPasswordResetTokenValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToSend2faMailDueToValidationError(array $inputData): void
    {
        $response = $this->postJson('/admin/resend-2fa-email', $inputData);
        $response->assertStatus(422);
    }

    public function testItSends2faMail(): void
    {
        Notification::fake();

        $admin = Admin::factory()->with2fa()->create();

        $response = $this->postJson('/admin/resend-2fa-email', ['email' => $admin->email]);

        $response->assertStatus(200);
        $response->assertSee("A 2fa otp has been resent to $admin->email");

        Notification::assertSentTo($admin, TwoFactorRequest::class);
    }

    public function testItFailsToVerify2faDueToInvalidOtp(): void
    {
        $admin = Admin::factory()->withOtp('1023')->with2fa()->create();
        $response = $this->postJson('/admin/verify-2fa-token', ['email' => $admin->email, 'otp' => '1025']);
        $response->assertStatus(400);
        $response->assertSee('Invalid OTP entered');
    }

    public function testItFailsToVerify2faDueToOtpExpired(): void
    {
        $admin = Admin::factory()->withOtp('1023', true)->with2fa()->create();
        $response = $this->postJson('/admin/verify-2fa-token', ['email' => $admin->email, 'otp' => '1023']);
        $response->assertStatus(400);
        $response->assertSee('Expired OTP entered');
    }

    public function testItVerifies2faOtp(): void
    {
        $admin = Admin::factory()->withOtp('1055')->with2fa()->create();
        $response = $this->postJson('/admin/verify-2fa-token', ['email' => $admin->email, 'otp' => '1055']);
        $response->assertStatus(200);
        $this->assertNull($admin->refresh()->otp);
        $this->assertNull($admin->refresh()->otp_expiry);
    }

    public function testItSends2faMailAfterLogin(): void
    {
        Notification::fake();

        $admin = Admin::factory()->with2fa()->create();

        $response = $this->postJson('/admin/login', ['email' => $admin->email, 'password' => 'password']);

        $response->assertStatus(200);
        $this->assertEquals($response->json()['message'], "A 2fa otp has been sent to $admin->email");

        Notification::assertSentTo($admin, TwoFactorRequest::class);
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
