<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
    }

    public function index(): JsonResponse
    {
        return $this->success(data: RoleResource::collection(Role::all()));
    }

    public function permissions(): JsonResponse
    {
        $data = $groupedPermissions = [];
        $systemPermissions = Permission::query()->orderBy('group_name')->get();
        foreach ($systemPermissions as $permission) {
            $groupedPermissions[$permission->group_name][] = new PermissionResource($permission); // @phpstan-ignore-line
        }
        foreach ($groupedPermissions as $group => $permissions) {
            $group = ucwords(str_replace('-', ' ', $group)); // @phpstan-ignore-line
            $data[] = compact('group', 'permissions');
        }

        return $this->success(data: $data);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        DB::beginTransaction();
        $role = Role::create(['name' => $request->name, 'guard_name' => config('permission.guard')]);
        $role->permissions()->sync($request->permissions);
        DB::commit();

        return $this->success(data: new RoleResource($role));
    }

    public function show(Role $role): JsonResponse
    {
        return $this->success(data: new RoleResource($role));
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return $this->success(data: new RoleResource($role));
    }

    public function delete(Role $role): JsonResponse
    {
        if ($role->id == 1) {
            return $this->failure('Can\'t delete default role.');
        }
        foreach ($role->users as $user) {
            $user->removeRole($role); // @phpstan-ignore-line
        }
        $role->syncPermissions([]);
        $role->delete();

        return $this->success();
    }
}
