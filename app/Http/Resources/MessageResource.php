<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar ?? null,
                ];
            }),
            'content' => $this->content,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'is_edited' => $this->is_edited,
            'edited_at' => $this->edited_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'mentions' => $this->whenLoaded('mentions', function() {
                return $this->mentions->map(function ($mention) {
                    return [
                        'id' => $mention->id,
                        'user_id' => $mention->user_id,
                        'user' => [
                            'id' => $mention->user->id,
                            'name' => $mention->user->name,
                        ],
                    ];
                });
            }),
        ];
    }
}
