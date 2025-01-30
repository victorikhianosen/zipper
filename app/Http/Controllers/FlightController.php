<?php

namespace App\Http\Controllers;

use App\Models\BookFlight;
use App\Models\FlightOffers;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use App\Services\FlightOfferService;
use App\Http\Requests\FlightOfferRequest;
use App\Http\Requests\FlightBookingRequest;
use App\Http\Resources\FlightOfferResource;
use App\Http\Resources\FlightBookingResource;

class FlightController extends Controller
{
    public function __construct(
        protected FlightService $flightService,
        protected FlightOfferService $flightOfferService
    ) {
    }

    public function getOffers(FlightOfferRequest $request): JsonResponse
    {
        $offers = $this->flightService->getOffers($request->validated());

        return $this->success(data: FlightOfferResource::collection($offers));
    }

    public function getOffer(FlightOffers $offer): JsonResponse
    {

        return $this->success(data: new FlightOfferResource($offer));
    }

    public function validatePrice(FlightOffers $offer): JsonResponse
    {
        $detail = $this->flightService->validatePrice($offer);

        return $this->success(data: $detail);
    }

    public function bookFlight(FlightBookingRequest $request): JsonResponse
    {
        $order = $this->flightService->bookFlight($request);

        return $this->success(message: 'Flight booking processing', data: $order);
    }


    public function bookFlightWithDeals(FlightBookingRequest $request)
    {
        $data = $this->flightService->bookFlightWithDeals($request);
        return $this->success(message: 'Flight booking processing', data: $data);

    }

    public function viewFlight(BookFlight $flight): JsonResponse
    {

        return $this->success(data: new FlightBookingResource($flight->load(['transactions'])));
    }

    public function getAirlines(): JsonResponse
    {

        return $this->success(data: $this->flightService->getAirlines());
    }

    // public function retryBookingForFailedPayment(): JsonResponse
    // {
    //     exit;
    //     $data = $this->flightService->retryBookingForFailedPayment();

    //     return $this->success(message: 'Flight booking processing', data: $data);
    // }

    // public function retryBookingForPendingPayment(): JsonResponse
    // {
    //     exit;
    //     $data = $this->flightService->retryBookingForPendingPayment();

    //     return $this->success(message: 'Flight booking processing', data: $data);
    // }

    public function completeFlightBooking(): JsonResponse
    {
        $data = $this->flightService->completeFlightBooking();

        return $this->success(message: 'Flight booking processing', data: $data);
    }

    public function addToWishList(FlightOffers $offer): JsonResponse
    {
        $this->flightOfferService->addToWishList($offer);

        return $this->success('Offer added to wishlist');
    }

    public function deleteFromWishList(FlightOffers $offer): JsonResponse
    {
        $this->flightOfferService->deleteFromWishList($offer);

        return $this->success('Offer removed from wishlist');
    }

    public function getWishLists(): JsonResponse
    {
        $data = $this->flightOfferService->getWishLists();

        return $this->success(data: FlightOfferResource::collection($data['data']), meta: $data['meta']);
    }
}
