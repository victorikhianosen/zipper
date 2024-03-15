<?php

namespace App\Services;

use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SettingService extends BaseService
{
    protected function model(): Model
    {
        return resolve(Setting::class);
    }

    public function create(SettingRequest $request): Model
    {
        return $this->model()->create($request->validated());
    }

    public function edit(SettingRequest $request): void
    {
        $data = $request->validated();
        $this->model()->update($data);
    }

    public function all(): Collection
    {
        return $this->model()->all();
    }
}
