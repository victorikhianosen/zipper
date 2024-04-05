<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordResetEmailRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyPasswordResetTokenRequest;
use App\Http\Resources\AdminResource;
use App\Models\PersonalAccessToken;
use App\Services\Admin\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service)
    {
        $this->middleware('auth:admin')->except([
            'login', 'resetPassword', 'resend2faEmail', 'verify2faToken',
            'sendPasswordResetEmail', 'verifyPasswordResetToken',
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->service->processLogin($request->validated());

        return $this->success($this->service->message ?? 'Login successful.', $data);
    }

    public function logout(): JsonResponse
    {
        $token = $this->authAdmin()->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            throw new BadRequestException('An error occurred', 400);
        }
        $token->delete();

        return $this->success('Logout successful.');
    }

    public function getAuthUser(): JsonResponse
    {
        return $this->success(data: new AdminResource($this->authAdmin()));
    }

    public function sendPasswordResetEmail(PasswordResetEmailRequest $request): JsonResponse
    {
        $this->service->sendPasswordResetEmail($request->email);

        return $this->success('Password reset email sent to '.$request->email);
    }

    public function verifyPasswordResetToken(VerifyPasswordResetTokenRequest $request): JsonResponse
    {
        $this->service->verifyPasswordResetToken($request->email, $request->otp);

        return $this->success('Token verified successfully.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->service->resetUserPassword($request->email, $request->password, $request->otp);

        return $this->success('Password was reset successfully');
    }

    public function resend2faEmail(PasswordResetEmailRequest $request): JsonResponse
    {
        $this->service->send2faEmail($request->email);

        return $this->success('A 2fa otp has been resent to '.$request->email);
    }

    public function verify2faToken(VerifyPasswordResetTokenRequest $request): JsonResponse
    {
        return $this->success(data: $this->service->verify2faToken($request->email, $request->otp));
    }

    public function toggle2fa(): JsonResponse
    {
        return $this->success(data: $this->service->toggle2fa($this->authAdmin()));
    }
}
