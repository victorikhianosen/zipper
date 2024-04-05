<?php

namespace App\Services;

use App\Enums\AuthType;
use App\Http\Factories\UserFactory;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\EmailVerificationRequest;
use App\Notifications\EmailVerificationSuccess;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Notifications\TwoFactorRequest;
use Exception;
use Google\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthService
{
    public ?string $message = null;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function registerUser(array $payload): array
    {
        $user = UserFactory::create($payload);

        $this->sendEmailVerification($user);

        return $this->returnUserAndToken($user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function logUserIn(array $payload, string $provider): array
    {
        return match ($provider) {
            'google' => $this->processGoogleSignIn($payload['user_token']),
            'apple' => $this->processAppleSignIn($payload['user_token']),
            default => $this->processLogin($payload),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function processGoogleSignIn(string $userGoogleToken): array
    {
        $client = new Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        try {
            $userData = $client->verifyIdToken($userGoogleToken);
        } catch (Exception $e) {
            throw new BadRequestException('Invalid token.', 400);
        }
        if (! $userData) {
            throw new BadRequestException('Invalid google token.', 400);
        }
        $user = User::where('email', $userData['email'])->first();
        if (! $user) {
            $data['first_name'] = $userData['family_name'];
            $data['last_name'] = $userData['given_name'];
            $data['email'] = $userData['email'];
            $data['avatar'] = $userData['picture'];
            $data['password'] = Hash::make(Str::random(8));
            $data['auth_type'] = AuthType::GOOGLE->value;
            $data['ref_code'] = null;
            $user = UserFactory::create($data);
        }
        if ($user['auth_type'] != AuthType::GOOGLE) {
            throw new BadRequestException('Auth type not valid.', 400);
        }

        return $this->returnUserAndToken($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function processAppleSignIn(string $userAppleToken): array
    {
        $userData = Socialite::driver('sign-in-with-apple')->userFromToken($userAppleToken); // @phpstan-ignore-line
        if (! $userData) {
            throw ValidationException::withMessages([
                'user_token' => ['Invalid token'],
            ]);
        }
        $email = $userData->email;
        $user = User::where('email', $email)->first();
        if (! $user) {
            $data['first_name'] = explode('@', $email)[0];
            $data['last_name'] = explode('@', $email)[0];
            $data['email'] = $email;
            $data['password'] = Hash::make(Str::random(8));
            $data['auth_type'] = AuthType::APPLE->value;
            $user = UserFactory::create($data);
        }
        if ($user['auth_type'] != AuthType::APPLE) {
            throw new BadRequestException('Auth type not valid.', 400);
        }

        return $this->returnUserAndToken($user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function processLogin(array $data): array
    {
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user['password'])) {
            throw ValidationException::withMessages([
                'email' => ['The credentials supplied is invalid.'],
            ]);
        }
        if ($user['auth_type'] != AuthType::PASSWORD) {
            throw new BadRequestException('Auth type not valid.', 400);
        }

        return $this->returnUserAndToken($user);
    }

    /**
     * @throws BadRequestException
     */
    public function sendEmailVerification(User $user): void
    {
        if ($user->email_verified_at !== null) {
            throw new BadRequestException('Email address already verified', 400);
        }

        $otp = app()->environment('production') ? (string) rand(1000, 9999) : '1234';

        $user->update([
            'verification_otp' => bcrypt($otp),
            'verification_otp_expiry' => now()->addHour(),
        ]);

        $user->notify(new EmailVerificationRequest($otp));
    }

    /**
     * @throws BadRequestException
     */
    public function verifyUserEmail(User $user, string $otp): void
    {
        if ($user->email_verified_at !== null) {
            throw new BadRequestException('Email address already verified', 400);
        }

        if (! $user->verification_otp || ! Hash::check($otp, $user->verification_otp)) {
            throw new BadRequestException('Invalid OTP entered', 400);
        }

        if (now()->gt($user->verification_otp_expiry)) {
            throw new BadRequestException('Expired OTP entered', 400);
        }

        $user->update([
            'verification_otp' => null,
            'verification_otp_expiry' => null,
            'email_verified_at' => now(),
        ]);

        $user->notify(new EmailVerificationSuccess());
        $user->notify(new AppNotification(
            'Welcome to Zippa Travel Champ',
            "Hi $user->first_name, \n Welcome to Zippa Travel.",
            ['database']
        ));
    }

    public function toggle2fa(User $user): UserResource
    {
        $user->update(['two_factor_enabled_at' => $user->two_factor_enabled_at ? null : now()]);

        return new UserResource($user);
    }

    public function send2faEmail(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid.']]);
        }

        if ($user->two_factor_enabled_at === null) {
            throw new BadRequestException('2FA not enabled!', 400);
        }

        $otp = app()->environment('production') ? (string) rand(1000, 9999) : '1234';

        $user->update([
            'verification_otp' => bcrypt($otp),
            'verification_otp_expiry' => now()->addHour(),
        ]);

        $user->notify(new TwoFactorRequest($otp));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BadRequestException|ValidationException
     */
    public function verify2faToken(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid.']]);
        }

        if ($user->two_factor_enabled_at === null) {
            throw new BadRequestException('2FA not enabled!', 400);
        }

        if (! $user->verification_otp || ! Hash::check($otp, $user->verification_otp)) {
            throw new BadRequestException('Invalid OTP entered', 400);
        }

        if (now()->gt($user->verification_otp_expiry)) {
            throw new BadRequestException('Expired OTP entered', 400);
        }

        $user->update([
            'verification_otp' => null,
            'verification_otp_expiry' => null,
        ]);

        return $this->returnUserAndToken($user, false);
    }

    /**
     * @throws ValidationException
     */
    public function sendPasswordResetEmail(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid.']]);
        }
        $token = app()->environment('production') ? (string) rand(1000, 9999) : '1234';
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $email], [
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);
        $user->notify(new PasswordResetRequest($token));
    }

    /**
     * Verify the reset code.
     *
     * @throws ValidationException
     */
    public function verifyPasswordResetToken(string $email, string $token): void
    {
        $passwordResetData = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $passwordResetData || ! Hash::check($token, $passwordResetData->token)) { // @phpstan-ignore-line
            throw ValidationException::withMessages(['otp' => 'The password reset token is invalid.']);
        }
        if (now()->subHour()->gt($passwordResetData->created_at)) { // @phpstan-ignore-line
            throw ValidationException::withMessages(['otp' => 'The password reset token has expired.']);
        }
    }

    /**
     * Reset the user's password.
     *
     * @throws ValidationException
     */
    public function resetUserPassword(string $email, string $password, string $token): void
    {
        $this->verifyPasswordResetToken($email, $token);
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['otp' => 'The password reset token is invalid.']);
        }
        $user->password = Hash::make($password);
        $user->save();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $user->notify(new PasswordResetSuccess());
    }

    public function toggleBiometrics(User $user, string $type): UserResource
    {
        $user->update(["{$type}_biometric_activated_at" => $user["{$type}_biometric_activated_at"] ? null : now()]);

        return new UserResource($user);
    }

    /**
     * @return array<string, string|UserResource>
     */
    protected function returnUserAndToken(User $user, bool $check2fa = true): array
    {
        if ($check2fa && $user->two_factor_enabled_at) {
            $otp = app()->environment('production') ? (string) rand(1000, 9999) : '1234';
            $user->update([
                'verification_otp' => bcrypt($otp),
                'verification_otp_expiry' => now()->addHour(),
            ]);
            $user->notify(new TwoFactorRequest($otp));
            $this->message = "A 2fa otp has been sent to $user->email";
            $user = new UserResource($user);

            return compact('user');
        }

        $token = $user->createToken('user')->plainTextToken;

        $user = new UserResource($user);

        return compact('user', 'token');
    }
}
