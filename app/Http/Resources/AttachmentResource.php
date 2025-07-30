<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for formatting attachment API responses.
 */
class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'path' => $this->path,
            'url' => $this->url,
            'disk' => $this->disk,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'metadata' => $this->metadata,
            'is_image' => $this->isImage(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => UserResource::make($this->whenLoaded('user')),
            'attachable' => $this->whenLoaded('attachable'),
        ];
    }
}
