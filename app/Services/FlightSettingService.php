<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Http\Requests\FlightSettingRequest;
use App\Models\FlightSetting;
use Illuminate\Database\Eloquent\Model;

class FlightSettingService extends BaseService
{
    public function __construct(
        protected FlightSetting $setting
    ) {

    }

    public function create(FlightSettingRequest $request): Model
    {
        $numRows = $this->setting->count();
        if ($numRows > 0) {
            throw new CustomException('Flight-Settings already created, please modify instead');
        }

        return $this->setting->create($request->validated());
    }

    public function edit(FlightSettingRequest $request): void
    {
        $data = $request->validated();
        $this->setting->first()?->update($data);
    }

    public function all(): ?Model
    {
        return $this->setting->first();
    }
}
