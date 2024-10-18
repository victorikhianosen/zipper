<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlightBookingResource;
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
