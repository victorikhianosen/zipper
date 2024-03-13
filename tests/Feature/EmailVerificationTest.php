<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationRequest;
use App\Notifications\EmailVerificationSuccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function testItFailsToResendEmailVerificationMailDueToAuthFailure(): void
    {
        $response = $this->postJson('/resend-verification-email');
        $response->assertStatus(401);
    }

    public function testItFailsToResendEmailVerificationMailDueToAlreadyVerified(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/resend-verification-email');
        $response->assertStatus(400);
        $response->assertSee('Email address already verified');
    }

    public function testItFailsToResendEmailVerificationMailDueToTooManyRequest(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user, ['*']);

        $tries = 0;
        while ($tries <= 3) {
            $this->postJson('/resend-verification-email');
            $tries++;
        }
        $response = $this->postJson('/resend-verification-email');
        $response->assertStatus(429);
    }

    public function testItResendsEmailVerificationMail(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/resend-verification-email');

        $response->assertStatus(200);

        Notification::assertSentTo(
            [$user],
            EmailVerificationRequest::class
        );
    }

    public function testItFailsToVerifyEmailDueToAuthFailure(): void
    {
        $response = $this->postJson('/verify-email');
        $response->assertStatus(401);
    }

    public function testItFailsToVerifyEmailDueToAlreadyVerified(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/verify-email', ['otp' => '0000']);
        $response->assertStatus(400);
        $response->assertSee('Email address already verified');
    }

    /**
     * @dataProvider failValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToVerifyEmailDueToValidationError(array $inputData): void
    {
        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/verify-email', $inputData);
        $response->assertStatus(422);
    }

    public function testItFailsToVerifyEmailDueToInvalidOtp(): void
    {
        $user = User::factory()->withOtp('1023')->unverified()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/verify-email', ['otp' => '1025']);
        $response->assertStatus(400);
        $response->assertSee('Invalid OTP entered');
    }

    public function testItFailsToVerifyEmailDueToOtpExpired(): void
    {
        $user = User::factory()->withOtp('1355', true)->unverified()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/verify-email', ['otp' => '1055']);
        $response->assertStatus(400);
        $response->assertSee('Invalid OTP entered');
    }

    public function testItVerifiesEmailAndSendNotification(): void
    {
        Notification::fake();

        $user = User::factory()->withOtp('1055')->unverified()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/verify-email', ['otp' => '1055']);
        $response->assertStatus(200);
        $response->assertSee('Email verified successfully');
        $this->assertNull($user->email_verification_otp);
        $this->assertNull($user->email_verification_expiry);
        $this->assertNotNull($user->email_verified_at);

        Notification::assertSentTo(
            [$user],
            EmailVerificationSuccess::class
        );
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
            'less_than_4_digits_otp' => [
                ['otp' => '1'],
                ['otp' => '10'],
                ['otp' => '105'],
            ],
            'more_than_4_digits_otp' => [
                ['otp' => '10285'],
                ['otp' => '104353'],
                ['otp' => '1234365'],
            ],
        ];
    }
}
