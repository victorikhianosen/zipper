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
            'email_verification_otp' => bcrypt($otp),
            'email_verification_expiry' => now()->addHour(),
        ]);

        $user->notify(new EmailVerificationRequest($otp));
    }

    /**
     * @return array<string, string|UserResource>
     */
    protected function returnUserAndToken(User $user): array
    {
        $token = $user->createToken('user')->plainTextToken;

        $user = new UserResource($user);

        return compact('user', 'token');
    }

    /**
     * @throws BadRequestException
     */
    public function verifyUserEmail(User $user, string $otp): void
    {
        if ($user->email_verified_at !== null) {
            throw new BadRequestException('Email address already verified', 400);
        }

        if (! $user->email_verification_otp || ! Hash::check($otp, $user->email_verification_otp)) {
            throw new BadRequestException('Invalid OTP entered', 400);
        }

        if (now()->gt($user->email_verification_expiry)) {
            throw new BadRequestException('Expired OTP entered', 400);
        }

        $user->update([
            'email_verification_otp' => null,
            'email_verification_expiry' => null,
            'email_verified_at' => now(),
        ]);

        $user->notify(new EmailVerificationSuccess());
        $user->notify(new AppNotification(
            'Welcome to Zippa Travel Champ',
            "Hi $user->first_name, \n Welcome to Zippa Travel.",
            ['database']
        ));
    }

    /**
     * @throws BadRequestException
     */
    public function sendPasswordResetEmail(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new BadRequestException();
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
     * @throws BadRequestException
     */
    public function verifyPasswordResetToken(string $email, string $token): void
    {
        $passwordResetData = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $passwordResetData || ! Hash::check($token, $passwordResetData->token)) { // @phpstan-ignore-line
            throw new BadRequestException('The password reset token is invalid.', 400);
        }
        if (now()->subHour()->gt($passwordResetData->created_at)) { // @phpstan-ignore-line
            throw new BadRequestException('The password reset token has expired.', 400);
        }
    }

    /**
     * Reset the user's password.
     *
     * @throws BadRequestException
     */
    public function resetUserPassword(string $email, string $password, string $token): void
    {
        $this->verifyPasswordResetToken($email, $token);
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new BadRequestException('The password reset token is invalid.');
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
}
