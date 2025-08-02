<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Export Resource
 * 
 * Transforms Export model data for API responses
 */
class ExportResource extends JsonResource
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
            'type' => $this->type,
            'entity' => $this->entity,
            'filters' => $this->filters,
            'file_path' => $this->file_path,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'is_in_progress' => $this->isInProgress(),
            'is_completed' => $this->isCompleted(),
            'has_failed' => $this->hasFailed(),
            'has_expired' => $this->hasExpired(),
            'is_ready_for_download' => $this->isReadyForDownload(),
            'download_url' => $this->when(
                $this->isReadyForDownload(),
                route('api.exports.download', $this->id)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
