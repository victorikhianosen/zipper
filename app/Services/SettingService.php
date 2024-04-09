<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;

class SettingService extends BaseService
{
    public function __construct(
        protected Setting $setting,
    ) {
    }

    public function create(SettingRequest $request): Model
    {
        $numRows = $this->setting->count();
        if ($numRows > 0) {
            throw new CustomException('Settings already created, please modify instead');
        }

        return $this->setting->create($request->validated());
    }

    public function edit(SettingRequest $request): void
    {
        $data = $request->validated();
        $this->setting->first()?->update($data);
    }

    public function all(): Model
    {
        return $this->setting->first(); //@phpstan-ignore-line
    }
}
