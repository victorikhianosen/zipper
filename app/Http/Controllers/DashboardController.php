<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlightBookingResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {

    }

    public function dashboardIndex(): JsonResponse
    {
        $data = $this->dashboardService->dashboardIndex();

        return $this->success(data: $data);
    }

    public function getFlightBookings(): JsonResponse
    {
        $flights = $this->dashboardService->getFlightBookings();

        return $this->success(data: FlightBookingResource::collection($flights['data']), meta: $flights['meta']);
    }

    public function getTransactions(): JsonResponse
    {
        $data = $this->dashboardService->getTransactions();

        return $this->success(data: $data);
    }
}
