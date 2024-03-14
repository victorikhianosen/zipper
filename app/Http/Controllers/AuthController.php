<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordResetEmailRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\VerifyPasswordResetTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\PersonalAccessToken;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->registerUser($request->validated());

        return $this->success(data: $user);
    }

    public function login(LoginRequest $request, string $provider): JsonResponse
    {
        $user = $this->authService->logUserIn($request->validated(), $provider);

        return $this->success($this->authService->message, $user);
    }

    public function logout(): JsonResponse
    {
        $this->invalidateToken();

        return $this->success();
    }

    public function getAuthUser(): JsonResponse
    {
        return $this->success(data: new UserResource($this->authUser()));
    }

    public function resendVerificationEmail(): JsonResponse
    {
        $this->authService->sendEmailVerification($this->authUser());

        return $this->success('Verification email resent to '.$this->authUser()->email);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $this->authService->verifyUserEmail($this->authUser(), $request->otp);

        return $this->success('Email verified successfully');
    }

    public function sendPasswordResetEmail(PasswordResetEmailRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetEmail($request->email);

        return $this->success('Password reset email sent to '.$request->email);
    }

    public function verifyPasswordResetToken(VerifyPasswordResetTokenRequest $request): JsonResponse
    {
        $this->authService->verifyPasswordResetToken($request->email, $request->otp);

        return $this->success('Token verified successfully.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetUserPassword($request->email, $request->password, $request->otp);

        return $this->success('Password was reset successfully');
    }

    public function resend2faEmail(PasswordResetEmailRequest $request): JsonResponse
    {
        $this->authService->send2faEmail($request->email);

        return $this->success('A 2fa otp has been resent to '.$request->email);
    }

    public function verify2faToken(VerifyPasswordResetTokenRequest $request): JsonResponse
    {
        $user = $this->authService->verify2faToken($request->email, $request->otp);

        return $this->success($this->authService->message, $user);
    }

    public function toggle2fa(): JsonResponse
    {
        return $this->success(data: $this->authService->toggle2fa($this->authUser()));
    }

    public function toggleBiometrics(string $type = 'login'): JsonResponse
    {
        return $this->success(data: $this->authService->toggleBiometrics($this->authUser(), $type));
    }

    public function deleteAccount(): JsonResponse
    {
        $this->authUser()->delete();
        $this->invalidateToken();

        return $this->success('Account deleted successfully');
    }

    private function invalidateToken(): void
    {
        $token = $this->authUser()->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            throw new BadRequestException('An error occurred', 400);
        }
        $token->delete();
    }
}
