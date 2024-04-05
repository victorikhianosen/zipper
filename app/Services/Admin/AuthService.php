<?php

namespace App\Services\Admin;

use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Notifications\TwoFactorRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthService
{
    public ?string $message = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function processLogin(array $data): array
    {
        $admin = Admin::where('email', $data['email'])->first();
        if (! $admin || ! Hash::check($data['password'], $admin['password'])) {
            throw ValidationException::withMessages([
                'email' => ['The credentials supplied is invalid.'],
            ]);
        }

        return $this->returnUserAndToken($admin);
    }

    public function toggle2fa(Admin $admin): AdminResource
    {
        $admin->update(['two_factor_enabled_at' => $admin->two_factor_enabled_at ? null : now()]);

        return new AdminResource($admin);
    }

    public function send2faEmail(string $email): void
    {
        $admin = Admin::where('email', $email)->first();
        if (! $admin) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid.']]);
        }

        if ($admin->two_factor_enabled_at === null) {
            throw new BadRequestException('2FA not enabled!', 400);
        }

        $otp = app()->environment('production') ? (string) rand(1000, 9999) : '1234';

        $admin->update([
            'otp' => bcrypt($otp),
            'otp_expiry' => now()->addHour(),
        ]);

        $admin->notify(new TwoFactorRequest($otp));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BadRequestException|ValidationException
     */
    public function verify2faToken(string $email, string $otp): array
    {
        $admin = Admin::where('email', $email)->first();
        if (! $admin) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid.']]);
        }

        if ($admin->two_factor_enabled_at === null) {
            throw new BadRequestException('2FA not enabled!', 400);
        }

        if (! $admin->otp || ! Hash::check($otp, $admin->otp)) {
            throw new BadRequestException('Invalid OTP entered', 400);
        }

        if (now()->gt($admin->otp_expiry)) {
            throw new BadRequestException('Expired OTP entered', 400);
        }

        $admin->update([
            'otp' => null,
            'otp_expiry' => null,
        ]);

        return $this->returnUserAndToken($admin, false);
    }

    /**
     * @throws ValidationException
     */
    public function sendPasswordResetEmail(string $email): void
    {
        $admin = Admin::where('email', $email)->first();
        if (! $admin) {
            throw ValidationException::withMessages(['email' => ['The email address is invalid.']]);
        }
        $token = app()->environment('production') ? (string) rand(1000, 9999) : '1234';
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $email], [
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);
        $admin->notify(new PasswordResetRequest($token));
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
        $admin = Admin::where('email', $email)->first();
        if (! $admin) {
            throw ValidationException::withMessages(['otp' => 'The password reset token is invalid.']);
        }
        $admin->password = Hash::make($password);
        $admin->save();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $admin->notify(new PasswordResetSuccess());
    }

    /**
     * @return array<string, string|AdminResource>
     */
    protected function returnUserAndToken(Admin $admin, bool $check2fa = true): array
    {
        if ($check2fa && $admin->two_factor_enabled_at) {
            $otp = app()->environment('production') ? (string) rand(1000, 9999) : '1234';
            $admin->update([
                'otp' => bcrypt($otp),
                'otp_expiry' => now()->addHour(),
            ]);
            $admin->notify(new TwoFactorRequest($otp));
            $this->message = "A 2fa otp has been sent to $admin->email";
            $admin = new AdminResource($admin);

            return compact('admin');
        }

        $token = $admin->createToken('admin')->plainTextToken;

        $admin = new AdminResource($admin);

        return compact('admin', 'token');
    }
}
