<?php
// app/Http/Resources/ActivityLogResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activity Log API Resource
 * 
 * Transforms activity log data for API responses
 */
class ActivityLogResource extends JsonResource
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
            'user_id' => $this->user_id,
            'workspace_id' => $this->workspace_id,
            'action' => $this->action,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'properties' => $this->properties,
            'ip_address' => $this->when($request->user()?->can('viewSensitive', $this->resource), $this->ip_address),
            'user_agent' => $this->when($request->user()?->can('viewSensitive', $this->resource), $this->user_agent),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar' => $this->user->avatar ?? null,
                ];
            }),
            
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                    'slug' => $this->workspace->slug,
                ];
            }),
            
            'subject' => $this->whenLoaded('subject', function () {
                $subject = $this->subject;
                if (!$subject) return null;
                
                return [
                    'id' => $subject->id,
                    'type' => get_class($subject),
                    'name' => $subject->name ?? $subject->title ?? "#{$subject->id}",
                    'url' => method_exists($subject, 'getUrl') ? $subject->getUrl() : null,
                ];
            }),
            
            // Computed attributes
            'time_ago' => $this->created_at->diffForHumans(),
            'is_recent' => $this->created_at->greaterThan(now()->subHours(24)),
        ];
    }
}
