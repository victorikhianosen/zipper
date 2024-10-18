<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlertRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AlertController extends Controller
{
    public function __construct(protected AlertService $service)
    {
    }

    public function index(): JsonResponse
    {
        $alerts = $this->service->fetchAllAlerts();

        return $this->success(data: AlertResource::collection($alerts['data']), meta: $alerts['meta']);
    }

    public function store(AlertRequest $request): Response
    {
        $alert = new AlertResource($this->service->create($request->validated()));

        return $this->success('Alert created successfully', $alert);
    }

    public function show(Alert $alert): Response
    {
        return $this->success(data: new AlertResource($this->service->show($alert)));
    }

    /**
     * @throws Throwable
     */
    public function update(AlertRequest $request, Alert $alert): JsonResponse
    {
        $alert = $this->service->update($alert, $request->validated());

        return $this->success('Alert updated successfully', new AlertResource($alert));
    }

    public function destroy(Alert $alert): JsonResponse
    {
        $this->service->destroy($alert);

        return $this->success('Alert deleted successfully');
    }

    public function restore(string|int $alert): Response
    {
        $alert = $this->service->restore($alert);

        return $this->success('Alert restored successfully', new AlertResource($alert));
    }

    /**
     * @throws Throwable
     */
    public function dispatch(Alert $alert): Response
    {
        $alert->markAsOngoing();

        return $this->success('Alert dispatched successfully', new AlertResource($alert->refresh()));
    }
}
