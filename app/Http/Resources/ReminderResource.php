<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reminder Resource
 * 
 * Transforms reminder data for API responses
 */
class ReminderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'remindable_type' => $this->remindable_type,
            'remindable_id' => $this->remindable_id,
            'remindable' => $this->whenLoaded('remindable'),
            'remind_at' => $this->remind_at?->toISOString(),
            'type' => $this->type,
            'is_sent' => $this->is_sent,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Additional computed fields
            'is_overdue' => $this->remind_at && $this->remind_at->isPast() && !$this->is_sent,
            'is_upcoming' => $this->remind_at && $this->remind_at->isFuture() && !$this->is_sent,
            'time_until_reminder' => $this->remind_at?->diffForHumans(),
        ];
    }
}
