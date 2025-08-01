<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class InvitationResource
 * 
 * Transforms invitation data for API responses
 * 
 * @package App\Http\Resources
 */
class InvitationResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Computed attributes
            'is_pending' => $this->isPending(),
            'is_expired' => $this->isExpired(),
            'can_be_accepted' => $this->canBeAccepted(),
            'expires_in_days' => $this->expires_at?->diffInDays(now()),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            'team' => $this->whenLoaded('team', function () {
                return [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                ];
            }),
            
            'invited_by' => $this->whenLoaded('invitedBy', function () {
                return [
                    'id' => $this->invitedBy->id,
                    'name' => $this->invitedBy->name,
                    'email' => $this->invitedBy->email,
                ];
            }),
            
            // Conditional fields
            'token' => $this->when(
                $request->user()?->can('viewToken', $this->resource),
                $this->token
            ),
        ];
    }
}