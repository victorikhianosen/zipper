<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlightBookingResource;
use App\Models\Transaction;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {

    }

    public function myFlightBookings(): JsonResponse
    {
        $data = $this->userService->myFlightBookings();

        return $this->success(data: FlightBookingResource::collection($data['data']), meta: $data['meta']);
    }

    public function myTransactions(): JsonResponse
    {
        $data = $this->userService->myTransactions();

        return $this->success(data: $data['data'], meta: $data['meta']);
    }

    public function singleTransaction(Transaction $transaction): JsonResponse
    {
        return $this->success(data: $transaction);
    }
}
