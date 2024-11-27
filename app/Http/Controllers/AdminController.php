<?php

namespace App\Http\Controllers;

use App\Enums\Status\UserStatus;
use App\Http\Resources\FlightBookingResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected AdminService $adminService
    ) {
    }

    public function getUsers(): JsonResponse
    {
        $users = $this->userService->getUsers();

        return $this->success(data: $users['data'], meta: $users['meta']);
    }

    public function getUserDetails(User $user): JsonResponse
    {
        return $this->success(data: UserResource::make($user));
    }

    public function toggleUserStatus(User $user): JsonResponse
    {
        $user->update([
            'status' => $user->status == UserStatus::ACTIVE
                ? UserStatus::RESTRICTED->value
                : UserStatus::ACTIVE->value,
        ]);

        return $this->success(data: UserResource::make($user->refresh()));
    }

    public function getUserFlightBookings(User $user): JsonResponse
    {
        $flights = $this->adminService->getUserFlightBookings($user);

        return $this->success(data: FlightBookingResource::collection($flights['data']), meta: $flights['meta']);
    }

    public function makeReseller(User $user): JsonResponse
    {
        $this->adminService->makeReseller($user);

        return $this->success('Account updated successfully');
    }
}
