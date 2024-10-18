<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public static string $email = 'johndoe@gmail.com';

    public static string $otp = '1234';

    public static string $password = 'password1234';

    public static string $invalidEmail = 'goke@gmail.com';

    public static string $invalidOtp = '4321';

    public function testItFailsToSendPasswordResetMailDueToTooManyRequest(): void
    {
        Notification::fake();
        $tries = 0;
        while ($tries <= 3) {
            $this->postJson('/send-password-reset-email');
            $tries++;
        }
        $response = $this->postJson('/send-password-reset-email');
        $response->assertStatus(429);
    }

    /**
     * @dataProvider failVerifyPasswordResetTokenValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToSendPasswordResetMailDueToValidationError(array $inputData): void
    {
        Notification::fake();
        $response = $this->postJson('/send-password-reset-email', $inputData);
        $response->assertStatus(422);
    }

    public function testItSendsPasswordResetMail(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/send-password-reset-email', ['email' => $user->email]);

        $response->assertStatus(200);
        $response->assertSee("Password reset email sent to $user->email");

        Notification::assertSentTo($user, PasswordResetRequest::class);
    }

    public function testItFailsToVerifyPasswordResetTokenDueToInvalidToken(): void
    {
        User::factory(['email' => self::$email])->create();

        DB::table('password_reset_tokens')->insert([
            'email' => static::$email,
            'token' => Hash::make(static::$otp),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/verify-password-reset-token', [
            'email' => static::$email,
            'otp' => static::$invalidOtp,
        ]);
        $response->assertStatus(422);
        $response->assertSee('The password reset token is invalid.');
    }

    public function testItFailsToVerifyPasswordResetTokenDueToExpiredToken(): void
    {
        User::factory(['email' => static::$email])->create();

        DB::table('password_reset_tokens')->insert([
            'email' => static::$email,
            'token' => Hash::make(static::$otp),
            'created_at' => now()->subHour(),
        ]);

        $response = $this->postJson('/verify-password-reset-token', [
            'email' => static::$email,
            'otp' => static::$otp,
        ]);
        $response->assertStatus(422);
        $response->assertSee('The password reset token has expired.');
    }

    /**
     * @dataProvider failVerifyPasswordResetTokenValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToVerifyPasswordResetTokenDueToValidationError(array $inputData): void
    {
        User::factory(['email' => self::$email])->create();

        DB::table('password_reset_tokens')->insert([
            'email' => static::$email,
            'token' => Hash::make(static::$otp),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/verify-password-reset-token', $inputData);
        $response->assertStatus(422);
    }

    public function testItVerifiesPasswordResetToken(): void
    {
        User::factory(['email' => self::$email])->create();

        DB::table('password_reset_tokens')->insert([
            'email' => static::$email,
            'token' => Hash::make(static::$otp),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/verify-password-reset-token', [
            'email' => static::$email,
            'otp' => static::$otp,
        ]);
        $response->assertStatus(200);
        $response->assertSee('Token verified successfully.');
    }

    /**
     * @dataProvider failPasswordResetValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToResetPasswordDueToValidationError(array $inputData): void
    {
        User::factory(['email' => static::$email])->create();
        $response = $this->postJson('/reset-password', $inputData);
        $response->assertStatus(422);
    }

    public function testItResetUserPasswordAndSendNotification(): void
    {
        Notification::fake();
        $user = User::factory(['email' => static::$email])->create();
        DB::table('password_reset_tokens')->insert([
            'email' => static::$email,
            'token' => Hash::make(static::$otp),
            'created_at' => now(),
        ]);
        $this->assertFalse(Hash::check(static::$password, $user->password));
        $response = $this->postJson('/reset-password', [
            'email' => static::$email,
            'otp' => static::$otp,
            'password' => static::$password,
        ]);
        $response->assertStatus(200);
        $this->assertTrue(Hash::check(static::$password, $user->refresh()->password));
        Notification::assertSentTo($user, PasswordResetSuccess::class);
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
                    'password' => static::$password,
                ],
            ],
            'less_than_4_digits_otp' => [
                [
                    'email' => static::$email,
                    'otp' => '1',
                    'password' => static::$password,
                ],
                [
                    'email' => static::$email,
                    'otp' => '103',
                    'password' => static::$password,
                ],
                [
                    'email' => static::$email,
                    'otp' => '103',
                    'password' => static::$password,
                ],
            ],
            'more_than_4_digits_otp' => [
                [
                    'email' => static::$email,
                    'otp' => '10234',
                    'password' => static::$password,
                ],
                [
                    'email' => static::$email,
                    'otp' => '102343',
                    'password' => static::$password,
                ],
                [
                    'email' => static::$email,
                    'otp' => '1023436',
                    'password' => static::$password,
                ],
            ],
            'less_than_8_digits_password' => [
                [
                    'email' => static::$email,
                    'otp' => static::$otp,
                    'password' => 'pass',
                ],
                [
                    'email' => static::$email,
                    'otp' => static::$otp,
                    'password' => 'passw',
                ],
                [
                    'email' => static::$email,
                    'otp' => static::$otp,
                    'password' => 'passwo',
                ],
                [
                    'email' => static::$email,
                    'otp' => static::$otp,
                    'password' => 'passwor',
                ],
            ],
        ];
    }
}
