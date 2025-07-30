<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Goal Resource
 * 
 * Transforms goal model into consistent JSON API response
 */
class GoalResource extends JsonResource
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
            'team_id' => $this->team_id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'progress' => $this->progress,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Status indicators
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_cancelled' => $this->isCancelled(),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            'team' => $this->whenLoaded('team', function () {
                return $this->team ? [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                ] : null;
            }),
            
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ];
            }),
            
            'tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title ?? $task->name,
                        'status' => $task->status,
                        'assigned_at' => $task->pivot->created_at?->toISOString(),
                    ];
                });
            }),
            
            // Computed fields
            'tasks_count' => $this->whenCounted('tasks'),
            'days_remaining' => $this->when($this->end_date, function () {
                return $this->end_date->diffInDays(now(), false);
            }),
            'duration_days' => $this->when($this->start_date && $this->end_date, function () {
                return $this->start_date->diffInDays($this->end_date);
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'available_statuses' => \App\Models\Goal::getStatuses(),
            ],
        ];
    }
}