<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightBookingRequest;
use App\Http\Requests\FlightOfferRequest;
use App\Http\Resources\FlightBookingResource;
use App\Http\Resources\FlightOfferResource;
use App\Models\BookFlight;
use App\Models\FlightOffers;
use App\Services\FlightOfferService;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;

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


    public function bookFlight(FlightBookingRequest $request)
    {
        $validated = $request->validated();

        // Prepare the data to be stored in the database
        $bookFlightData = [
            'offer_id' => $validated['offer_id'],
            'flight_cost' => 0,
            'flight_order_ref' => uniqid(),
            'carrier' => $validated['carrier'],
            'passengers' => json_encode($validated['passengers']),
            'raw_response' => json_encode($validated),
            'request_body' => json_encode($request->all()),
        ];

        // Save the data to the database
        $bookFlight = BookFlight::create($bookFlightData);

        // Return a simplified response with booking_id
        return $this->success(
            'Flight booked successfully',
            ['booking_id' => $bookFlight->id],  // Return only the booking_id
            [],
            201,
            'success'
        );
    }

    // public function bookFlight(FlightBookingRequest $request): JsonResponse
    // {
    //     $order = $this->flightService->bookFlight($request);

    //     return $this->success(message: 'Flight booking processing', data: $order);
    // }

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
