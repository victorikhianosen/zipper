<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppUpdateRequest;
use App\Models\AppUpdate;
use Illuminate\Http\JsonResponse;

class AppUpdateController extends Controller
{
    public function create(AppUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $update = AppUpdate::create($data);

        return $this->success(data: $update);
    }

    public function edit(AppUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        /** @var AppUpdate $update */
        $update = AppUpdate::query()
            ->whereId($data['id'])->first();
        unset($data['id']);
        $update->update($data);

        return $this->success(message: 'Resource updated successfully');
    }

    public function all(): JsonResponse
    {
        $data = AppUpdate::all();

        return $this->success(data: $data);
    }
}
