<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ConversationResource extends JsonResource
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
            'name' => $this->getConversationName(),
            'display_name' => $this->getDisplayName(),
            'type' => $this->type,
            'is_private' => $this->is_private,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'users' => $this->whenLoaded('users', function() {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar ?? null,
                        'role' => $user->pivot->role,
                        'joined_at' => $user->pivot->joined_at,
                        'last_read_at' => $user->pivot->last_read_at,
                    ];
                });
            }),
            'users_count' => $this->whenCounted('users'),
            'messages' => $this->whenLoaded('messages', function() {
                return MessageResource::collection($this->messages);
            }),
            'messages_count' => $this->whenCounted('messages'),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'unread_count' => $this->getUnreadCount(),
            'current_user_role' => $this->getCurrentUserRole(),
        ];
    }
    
    /**
     * Get the conversation name based on type.
     *
     * @return string|null
     */
    protected function getConversationName(): ?string
    {
        // For direct messages, return null as we use display_name instead
        if ($this->type === 'direct') {
            return null;
        }
        
        return $this->name;
    }
    
    /**
     * Get a display name for the conversation.
     * For direct messages, use the other user's name.
     * For groups/channels, use the conversation name.
     *
     * @return string
     */
    protected function getDisplayName(): string
    {
        if ($this->type === 'direct' && $this->relationLoaded('users')) {
            $otherUser = $this->users->where('id', '!=', Auth::id())->first();
            return $otherUser ? $otherUser->name : 'Unknown User';
        }
        
        return $this->name ?? 'Unnamed Conversation';
    }
    
    /**
     * Get the number of unread messages for the current user.
     *
     * @return int|null
     */
    protected function getUnreadCount(): ?int
    {
        if (!$this->relationLoaded('users')) {
            return null;
        }
        
        $currentUser = $this->users->firstWhere('id', Auth::id());
        if (!$currentUser) {
            return null;
        }
        
        $lastReadAt = $currentUser->pivot->last_read_at;
        if (!$lastReadAt) {
            return $this->messages_count ?? null;
        }
        
        return $this->messages()
            ->where('created_at', '>', $lastReadAt)
            ->where('user_id', '!=', Auth::id())
            ->count();
    }
    
    /**
     * Get the current user's role in this conversation.
     *
     * @return string|null
     */
    protected function getCurrentUserRole(): ?string
    {
        if (!$this->relationLoaded('users')) {
            return null;
        }
        
        $currentUser = $this->users->firstWhere('id', Auth::id());
        
        return $currentUser ? $currentUser->pivot->role : null;
    }
}
