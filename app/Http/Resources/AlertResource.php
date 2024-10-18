<?php

namespace App\Http\Resources;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Alert $resource
 */
class AlertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $creator = $this->whenLoaded('creator')
            ? ['creator' => new AdminResource($this->resource->creator)]
            : [];
        $users = $this->whenLoaded('users')
            ? ['users' => UserResource::collection($this->resource->users)]
            : [];

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'status' => $this->resource->status,
            'target' => $this->resource->target,
            'user_count' => $this->resource->user_count,
            'channels' => $this->resource->channels,
            'failed_note' => $this->resource->failed_note,
            'created_at' => $this->resource->created_at,
            'dispatch_datetime' => $this->resource->dispatched_at,
        ] + $creator + $users;
    }
}
