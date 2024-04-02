<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightBookingRequest;
use App\Http\Requests\FlightOfferRequest;
use App\Http\Resources\FlightOfferResource;
use App\Models\FlightOffers;
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

    public function validatePrice(FlightOffers $offer): JsonResponse
    {
        $detail = $this->flightService->validatePrice($offer);

        return $this->success(data: $detail);
    }

    public function bookFlight(FlightBookingRequest $request): JsonResponse
    {
        $order = $this->flightService->bookFlight($request);

        return $this->success(data: $order);
    }
}
