<?php
// app/Http/Resources/PipelineResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pipeline Resource
 * 
 * Transforms pipeline models into consistent JSON API responses.
 */
class PipelineResource extends JsonResource
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
            'project_id' => $this->project_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
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
            
            'project' => $this->whenLoaded('project', function () {
                return $this->project ? [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                ] : null;
            }),
            
            'statuses' => $this->whenLoaded('statuses', function () {
                return $this->statuses->map(function ($status) {
                    return [
                        'id' => $status->id,
                        'name' => $status->name,
                        'slug' => $status->slug,
                        'color' => $status->color,
                        'icon' => $status->icon,
                        'type' => $status->type,
                        'order' => $status->pivot->order,
                    ];
                })->sortBy('order')->values();
            }),
            
            // Additional counts
            'statuses_count' => $this->whenCounted('statuses'),
        ];
    }
}
