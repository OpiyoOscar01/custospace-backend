<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Project Resource
 * 
 * Transforms project models into consistent JSON API responses.
 */
class ProjectResource extends JsonResource
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
            'team_id' => $this->team_id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'priority' => $this->priority,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'budget' => $this->budget,
            'progress' => $this->progress,
            'is_template' => $this->is_template,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Computed attributes
            'formatted_status' => $this->formatted_status,
            'formatted_priority' => $this->formatted_priority,
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_on_hold' => $this->isOnHold(),
            
            // Relationships - loaded conditionally
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                    'slug' => $this->workspace->slug,
                ];
            }),
            
            'team' => $this->whenLoaded('team', function () {
                return $this->team ? [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                    'slug' => $this->team->slug,
                ] : null;
            }),
            
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                    'avatar' => $this->owner->avatar,
                ];
            }),
            
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'role' => $user->pivot->role,
                        'joined_at' => $user->pivot->created_at,
                    ];
                });
            }),
            
            'pipelines' => $this->whenLoaded('pipelines', function () {
                return $this->pipelines->map(function ($pipeline) {
                    return [
                        'id' => $pipeline->id,
                        'name' => $pipeline->name,
                        'slug' => $pipeline->slug,
                        'is_default' => $pipeline->is_default,
                    ];
                });
            }),
            
            // Additional computed data
            'users_count' => $this->whenCounted('users'),
            'pipelines_count' => $this->whenCounted('pipelines'),
        ];
    }
}
