<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for formatting media API responses.
 */
class MediaResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'path' => $this->path,
            'url' => $this->url,
            'disk' => $this->disk,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'collection' => $this->collection,
            'metadata' => $this->metadata,
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'workspace' => WorkspaceResource::make($this->whenLoaded('workspace')),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
