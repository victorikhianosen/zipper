<?php

namespace App\Http\Controllers;

use App\Services\FlightService;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function __construct(
        protected FlightService $flightService
    ) {
    }

    public function getCities(): JsonResponse
    {
        $cities = $this->flightService->getCities();

        return $this->success(data: $cities['data'], meta: $cities['meta']);
    }
}
