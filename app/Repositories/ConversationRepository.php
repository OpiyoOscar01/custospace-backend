<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationRepository implements ConversationRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getPaginatedConversations(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = Conversation::query();
        
        if (isset($criteria['workspace_id'])) {
            $query->where('workspace_id', $criteria['workspace_id']);
        }
        
        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }
        
        if (isset($criteria['is_private'])) {
            $query->where('is_private', $criteria['is_private']);
        }
        
        return $query->with(['users'])
                    ->latest()
                    ->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getConversationsForUser(int $userId, array $filters = []): Collection
    {
        $query = Conversation::whereHas('users', function ($query) use ($userId) {
            $query->where('users.id', $userId);
        });
        
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        return $query->with([
                'users' => function ($query) {
                    $query->select('users.id', 'name', 'email');
                },
                'latestMessage'
            ])
            ->withCount('messages')
            ->latest('updated_at')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getConversationById(int $id): ?Conversation
    {
        return Conversation::with(['users', 'messages' => function($query) {
            $query->latest()->limit(50);
        }])->find($id);
    }

    /**
     * @inheritDoc
     */
    public function createConversation(array $data): Conversation
    {
        $conversationData = [
            'workspace_id' => $data['workspace_id'],
            'name' => $data['name'] ?? null,
            'type' => $data['type'],
            'is_private' => $data['is_private'] ?? true,
        ];
        
        return DB::transaction(function () use ($conversationData, $data) {
            $conversation = Conversation::create($conversationData);
            
            // Add authenticated user as owner
            $conversation->users()->attach(Auth::id(), [
                'role' => 'owner',
                'joined_at' => now(),
            ]);
            
            // Add other users
            if (!empty($data['user_ids'])) {
                $userIds = array_diff($data['user_ids'], [Auth::id()]);

                $this->addUsers($conversation, $userIds);
            }
            
            return $conversation->fresh(['users']);
        });
    }

    /**
     * @inheritDoc
     */
    public function updateConversation(Conversation $conversation, array $data): Conversation
    {
        $conversation->update($data);
        
        return $conversation->fresh();
    }

    /**
     * @inheritDoc
     */
    public function deleteConversation(Conversation $conversation): bool
    {
        return $conversation->delete();
    }

    /**
     * @inheritDoc
     */
    public function addUsers(Conversation $conversation, array $userIds, string $role = 'member'): void
    {
        $pivotData = collect($userIds)->mapWithKeys(function ($userId) use ($role) {
            return [$userId => [
                'role' => $role,
                'joined_at' => now(),
            ]];
        })->toArray();
        
        $conversation->users()->attach($pivotData);
    }

    /**
     * @inheritDoc
     */
    public function removeUsers(Conversation $conversation, array $userIds): void
    {
        // Don't allow removing the last owner
        if ($conversation->users()->wherePivot('role', 'owner')->count() <= 1) {
            // Check if we're trying to remove an owner
            $ownerBeingRemoved = $conversation->users()
                ->wherePivot('role', 'owner')
                ->whereIn('users.id', $userIds)
                ->exists();
                
            if ($ownerBeingRemoved) {
                throw new \Exception('Cannot remove the last owner from the conversation');
            }
        }
        
        $conversation->users()->detach($userIds);
    }

    /**
     * @inheritDoc
     */
    public function updateUserRole(Conversation $conversation, int $userId, string $role): void
    {
        // If changing from owner to something else, make sure there's another owner
        if ($role !== 'owner') {
            $currentRole = $conversation->users()->where('users.id', $userId)->first()->pivot->role ?? null;
            
            if ($currentRole === 'owner' && 
                $conversation->users()->wherePivot('role', 'owner')->count() <= 1) {
                throw new \Exception('Cannot change role: conversation needs at least one owner');
            }
        }
        
        $conversation->users()->updateExistingPivot($userId, [
            'role' => $role,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function markAsRead(Conversation $conversation, int $userId): void
    {
        $conversation->users()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findOrCreateDirectConversation(int $workspaceId, int $user1Id, int $user2Id): Conversation
    {
        // Find conversations that both users are part of
        $commonConversations = Conversation::where('workspace_id', $workspaceId)
            ->where('type', 'direct')
            ->whereHas('users', function ($query) use ($user1Id) {
                $query->where('users.id', $user1Id);
            })
            ->whereHas('users', function ($query) use ($user2Id) {
                $query->where('users.id', $user2Id);
            })
            ->get();

        // If there are multiple direct conversations between these users (shouldn't happen),
        // return the most recently updated one
        if ($commonConversations->count() > 0) {
            return $commonConversations->sortByDesc('updated_at')->first();
        }
        
        // Create a new direct conversation
        $conversation = Conversation::create([
            'workspace_id' => $workspaceId,
            'name' => null,
            'type' => 'direct',
            'is_private' => true,
        ]);
        
        // Add both users
        $conversation->users()->attach([
            $user1Id => ['role' => 'member', 'joined_at' => now()],
            $user2Id => ['role' => 'member', 'joined_at' => now()],
        ]);
        
        return $conversation->fresh(['users']);
    }
}
