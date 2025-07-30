<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentionResource extends JsonResource
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
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar ?? null,
                ];
            }),
            'mentionable_type' => $this->mentionable_type,
            'mentionable_id' => $this->mentionable_id,
            'mentionable' => $this->getFormattedMentionable(),
            'mentioned_by_id' => $this->mentioned_by_id,
            'mentioned_by' => $this->whenLoaded('mentionedBy', function() {
                return [
                    'id' => $this->mentionedBy->id,
                    'name' => $this->mentionedBy->name,
                    'avatar' => $this->mentionedBy->avatar ?? null,
                ];
            }),
            'is_read' => $this->is_read,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    
    /**
     * Get formatted mentionable data based on type.
     *
     * @return array|null
     */
    protected function getFormattedMentionable(): ?array
    {
        if (!$this->relationLoaded('mentionable') || !$this->mentionable) {
            return null;
        }
        
        $mentionable = $this->mentionable;
        
        $data = [
            'id' => $mentionable->id,
            'content' => $mentionable->content,
            'created_at' => $mentionable->created_at,
        ];
        
        if ($mentionable->relationLoaded('user')) {
            $data['user'] = [
                'id' => $mentionable->user->id,
                'name' => $mentionable->user->name,
                'avatar' => $mentionable->user->avatar ?? null,
            ];
        }
        
        if ($this->mentionable_type === 'App\\Models\\Message') {
            $data['conversation_id'] = $mentionable->conversation_id;
        } elseif ($this->mentionable_type === 'App\\Models\\Comment') {
            $data['commentable_type'] = $mentionable->commentable_type;
            $data['commentable_id'] = $mentionable->commentable_id;
        }
        
        return $data;
    }
}
