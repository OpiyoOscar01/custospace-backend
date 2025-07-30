<?php
// app/Http/Resources/AuditLogResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Audit Log API Resource
 * 
 * Transforms audit log data for API responses
 */
class AuditLogResource extends JsonResource
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
            'event' => $this->event,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'old_values' => $this->when($request->user()?->can('viewSensitive', $this->resource), $this->old_values),
            'new_values' => $this->when($request->user()?->can('viewSensitive', $this->resource), $this->new_values),
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
            
            'auditable' => $this->whenLoaded('auditable', function () {
                $auditable = $this->auditable;
                if (!$auditable) return null;
                
                return [
                    'id' => $auditable->id,
                    'type' => get_class($auditable),
                    'name' => $auditable->name ?? $auditable->title ?? "#{$auditable->id}",
                    'url' => method_exists($auditable, 'getUrl') ? $auditable->getUrl() : null,
                ];
            }),
            
            // Computed attributes
            'changes' => $this->when($request->user()?->can('viewSensitive', $this->resource), $this->changes),
            'time_ago' => $this->created_at->diffForHumans(),
            'event_label' => $this->getEventLabel(),
        ];
    }
    
    /**
     * Get human-readable event label.
     */
    private function getEventLabel(): string
    {
        return match($this->event) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            default => ucfirst($this->event),
        };
    }
}
