<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @property DatabaseNotification $resource
 */
class NotificationResource extends JsonResource
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
            'title' => $this->resource->data['title'],
            'body' => $this->resource->data['body'],
            'read' => $this->resource->read_at !== null,
            'created_at' => $this->resource->created_at,
        ];
    }
}
