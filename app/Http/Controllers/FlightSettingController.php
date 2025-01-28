<?php

namespace App\Http\Controllers;

use App\Models\FlightOffers;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use App\Services\FlightSettingService;
use App\Http\Requests\FlightDealRequest;
use App\Http\Requests\FlightSettingRequest;
use App\Http\Resources\FlightOfferResource;

class FlightSettingController extends Controller
{
    public function __construct(
        protected FlightSettingService $settingService,
        protected FlightService $flightService
    ) {
    }

    public function create(FlightSettingRequest $settingRequest): JsonResponse
    {
        $data = $this->settingService->create($settingRequest);

        return $this->success(data: $data);
    }

    public function edit(FlightSettingRequest $settingRequest): JsonResponse
    {
        $this->settingService->edit($settingRequest);

        return $this->success(message: 'Resource updated successfully');
    }

    public function all(): JsonResponse
    {
        $data = $this->settingService->all();
        if (blank($data)) {
            $data = [];
        }

        return $this->success(data: $data);
    }

    public function createFlightDeals(FlightDealRequest $request): JsonResponse
    {
        $data = $this->settingService->getOffers($request->validated());

        return $this->success(data: FlightOfferResource::collection($data));
    }

    public function getFlightDeals(): JsonResponse
    {
        $deals = $this->settingService->getFlightDeals();

        return $this->success(data: FlightOfferResource::collection($deals['data']), meta: $deals['meta']);
    }

    public function deleteFlightDeal(FlightOffers $offer): JsonResponse
    {
        $offer->delete();

        return $this->success(message: 'Deals deleted successfully');
    }

    public function singleFlightDeal(FlightOffers $offer): JsonResponse
    {
        return $this->success(data: new FlightOfferResource($offer));
    }
}
