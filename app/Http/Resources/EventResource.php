<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Event API Resource
 * 
 * Transforms event data for consistent API responses
 */
class EventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->end_date?->format('Y-m-d H:i:s'),
            'all_day' => $this->all_day,
            'location' => $this->location,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'metadata' => $this->metadata,
            'is_cancelled' => $this->isCancelled(),
            'is_rescheduled' => $this->isRescheduled(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            'participants' => EventParticipantResource::collection($this->whenLoaded('participants')),
            'participants_count' => $this->whenCounted('participants'),
            
            // Statistics
            'participants_stats' => $this->when($this->relationLoaded('participants'), function () {
                return [
                    'total' => $this->participants->count(),
                    'accepted' => $this->participants->where('status', 'accepted')->count(),
                    'declined' => $this->participants->where('status', 'declined')->count(),
                    'pending' => $this->participants->where('status', 'pending')->count(),
                    'tentative' => $this->participants->where('status', 'tentative')->count(),
                ];
            }),
        ];
    }

    /**
     * Get human readable type label
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'meeting' => 'Meeting',
            'deadline' => 'Deadline',
            'reminder' => 'Reminder',
            'other' => 'Other',
            default => ucfirst($this->type)
        };
    }

    /**
     * Check if event is cancelled
     */
    private function isCancelled(): bool
    {
        return isset($this->metadata['cancelled']) && $this->metadata['cancelled'] === true;
    }

    /**
     * Check if event is rescheduled
     */
    private function isRescheduled(): bool
    {
        return isset($this->metadata['rescheduled']) && $this->metadata['rescheduled'] === true;
    }
}