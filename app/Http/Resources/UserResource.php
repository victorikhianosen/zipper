<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'username' => $this->resource->username,
            'phone' => $this->resource->phone,
            'phone_code' => $this->resource->phone_code,
            'ref_code' => $this->resource->ref_code,
            'gender' => $this->resource->gender,
            'status' => $this->resource->status,
            'auth_type' => $this->resource->auth_type,
            'email_verified' => $this->resource->email_verified_at !== null,
            'two_factor_enabled' => $this->resource->two_factor_enabled_at !== null,
            'login_biometric_activated' => $this->resource->login_biometric_activated_at !== null,
            'avatar' => $this->resource->avatar,
            'point_bonus' => $this->resource->wallet?->point_bonus,
        ];
    }
}
