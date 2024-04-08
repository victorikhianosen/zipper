<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlightBookingResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {

    }

    public function getFlightBookings(): JsonResponse
    {
        $data = $this->userService->getFlightBookings();

        return $this->success(data: FlightBookingResource::collection($data['data']), meta: $data['meta']);
    }
}
