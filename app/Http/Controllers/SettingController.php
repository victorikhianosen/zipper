<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {
    }

    public function create(SettingRequest $settingRequest): JsonResponse
    {
        $data = $this->settingService->create($settingRequest);

        return $this->success(data: $data);
    }

    public function edit(SettingRequest $settingRequest): JsonResponse
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
