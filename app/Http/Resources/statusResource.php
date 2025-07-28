<?php
// app/Http/Resources/StatusResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Status Resource
 * 
 * Transforms status models into consistent JSON API responses.
 */
class StatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'icon' => $this->icon,
            'order' => $this->order,
            'type' => $this->type,
            'formatted_type' => $this->formatted_type,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships - loaded conditionally
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            'pipelines' => $this->whenLoaded('pipelines', function () {
                return $this->pipelines->map(function ($pipeline) {
                    return [
                        'id' => $pipeline->id,
                        'name' => $pipeline->name,
                        'order' => $pipeline->pivot->order,
                    ];
                });
            }),
            
            // Additional counts
            'pipelines_count' => $this->whenCounted('pipelines'),
        ];
    }
}
