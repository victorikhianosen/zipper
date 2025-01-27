<?php

namespace App\Http\Controllers;

use App\Models\FlightOffers;
use Illuminate\Http\JsonResponse;
use App\Services\FlightOfferService;
use App\Traits\RespondsWithHttpStatus;
use App\Http\Resources\FlightOfferResource;

class FlightOffersController extends Controller
{

    use RespondsWithHttpStatus;

    protected FlightOfferService $flightOfferService;

    public function __construct(FlightOfferService $flightOfferService)
    {
        $this->flightOfferService = $flightOfferService;
    }

    public function getFlightOffers(): JsonResponse
    {
        $deals = $this->flightOfferService->getAllDeals();
        return $this->success(
            data: FlightOfferResource::collection($deals['data']),
            meta: $deals['meta']
        );
    }
}
