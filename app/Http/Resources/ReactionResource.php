<?php
// app/Http/Resources/ReactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reaction API Resource
 * 
 * Transforms reaction data for API responses
 */
class ReactionResource extends JsonResource
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
            'reactable_type' => $this->reactable_type,
            'reactable_id' => $this->reactable_id,
            'type' => $this->type,
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
            
            'reactable' => $this->whenLoaded('reactable', function () {
                $reactable = $this->reactable;
                if (!$reactable) return null;
                
                return [
                    'id' => $reactable->id,
                    'type' => get_class($reactable),
                    'name' => $reactable->name ?? $reactable->title ?? "#{$reactable->id}",
                    'url' => method_exists($reactable, 'getUrl') ? $reactable->getUrl() : null,
                ];
            }),
            
            // Computed attributes
            'type_label' => $this->getTypeLabel(),
            'type_emoji' => $this->getTypeEmoji(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
    
    /**
     * Get human-readable type label.
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'thumbs_up' => 'Thumbs Up',
            'thumbs_down' => 'Thumbs Down',
            'heart' => 'Heart',
            'laugh' => 'Laugh',
            'wow' => 'Wow',
            'sad' => 'Sad',
            'angry' => 'Angry',
            'celebrate' => 'Celebrate',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
    
    /**
     * Get emoji representation of reaction type.
     */
    private function getTypeEmoji(): string
    {
        return match($this->type) {
            'thumbs_up' => '👍',
            'thumbs_down' => '👎',
            'heart' => '❤️',
            'laugh' => '😂',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😠',
            'celebrate' => '🎉',
            default => '👍',
        };
    }
}
