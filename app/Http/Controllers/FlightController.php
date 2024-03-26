<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightOfferRequest;
use App\Http\Resources\FlightOfferResource;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;

class FlightController extends Controller
{
    public function __construct(
        protected FlightService $flightService
    ) {
    }

    public function getOffers(FlightOfferRequest $request): JsonResponse
    {

        $offers = $this->flightService->getOffers($request->validated());

        return $this->success(data: FlightOfferResource::collection($offers));
    }
}
