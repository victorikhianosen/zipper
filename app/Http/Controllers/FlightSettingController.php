<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightSettingRequest;
use App\Services\FlightSettingService;
use Illuminate\Http\JsonResponse;

class FlightSettingController extends Controller
{
    public function __construct(protected FlightSettingService $settingService)
    {

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

        return $this->success(data: $data);
    }
}
