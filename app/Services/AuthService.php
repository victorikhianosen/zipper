<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\EmailVerificationRequest;
use App\Notifications\EmailVerificationSuccess;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function registerUser(array $payload): array
    {
        $user = User::create($payload)->refresh();

        $this->sendEmailVerification($user);

        return $this->returnUserAndToken($user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function logUserIn(array $payload): array
    {
        $user = User::where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user['password'])) {
            throw ValidationException::withMessages([
                'email' => ['The credentials supplied is invalid.'],
            ]);
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

        $otp = app()->environment('production') ? (string) rand(100000, 999999) : '123456';

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
        $token = app()->environment('production') ? (string) rand(100000, 999999) : '123456';
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
}
