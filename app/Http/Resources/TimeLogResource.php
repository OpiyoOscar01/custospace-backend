<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TimeLogResource
 * 
 * API resource for time log responses
 */
class TimeLogResource extends JsonResource
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
            'task_id' => $this->task_id,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'duration' => $this->duration,
            'duration_formatted' => $this->formatDuration(),
            'description' => $this->description,
            'is_billable' => $this->is_billable,
            'hourly_rate' => $this->hourly_rate ? (float) $this->hourly_rate : null,
            'total_earnings' => $this->getTotalEarnings(),
            'is_running' => $this->isRunning(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            'task' => $this->whenLoaded('task', function () {
                return [
                    'id' => $this->task->id,
                    'title' => $this->task->title,
                    'description' => $this->task->description,
                    'status' => $this->task->status ?? null,
                    'priority' => $this->task->priority ?? null,
                ];
            }),
        ];
    }

    /**
     * Format duration in human-readable format.
     */
    protected function formatDuration(): string
    {
        if (!$this->duration) {
            return '0m';
        }

        $hours = intval($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }
}
