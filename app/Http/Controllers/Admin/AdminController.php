<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function __construct(protected AdminService $service)
    {
        $this->middleware('auth:admin');
    }

    public function index(): JsonResponse
    {
        $admins = $this->service->fetchAllAdmins();

        return $this->success(data: AdminResource::collection($admins['data']), meta: $admins['meta']);
    }

    public function store(AdminRequest $request): JsonResponse
    {
        $data = $this->service->createAdmin($request->validated());

        return $this->success('Admin added successfully!', $data);
    }

    public function show(Admin $admin): JsonResponse
    {
        return $this->success(data: new AdminResource($admin));
    }

    public function update(Admin $admin): JsonResponse
    {
        $admin = $this->service->toggleAdminStatus($admin);

        return $this->success('Admin updated successfully', new AdminResource($admin));
    }

    public function changeRole(Admin $admin, Role $role): JsonResponse
    {
        if ($admin->id == 1) {
            return $this->failure('Can\'t mutate default admin');
        }
        $admin->syncRoles($role);

        return $this->success(data: new AdminResource($admin));
    }
}
