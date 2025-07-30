<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepositoryInterface
{
    /**
     * Get paginated conversations.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedConversations(array $criteria, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Get all conversations for a user.
     *
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getConversationsForUser(int $userId, array $filters = []): Collection;
    
    /**
     * Get a conversation by ID.
     *
     * @param int $id
     * @return Conversation|null
     */
    public function getConversationById(int $id): ?Conversation;
    
    /**
     * Create a new conversation.
     *
     * @param array $data
     * @return Conversation
     */
    public function createConversation(array $data): Conversation;
    
    /**
     * Update an existing conversation.
     *
     * @param Conversation $conversation
     * @param array $data
     * @return Conversation
     */
    public function updateConversation(Conversation $conversation, array $data): Conversation;
    
    /**
     * Delete a conversation.
     *
     * @param Conversation $conversation
     * @return bool
     */
    public function deleteConversation(Conversation $conversation): bool;
    
    /**
     * Add users to a conversation.
     *
     * @param Conversation $conversation
     * @param array $userIds
     * @param string $role
     * @return void
     */
    public function addUsers(Conversation $conversation, array $userIds, string $role = 'member'): void;
    
    /**
     * Remove users from a conversation.
     *
     * @param Conversation $conversation
     * @param array $userIds
     * @return void
     */
    public function removeUsers(Conversation $conversation, array $userIds): void;
    
    /**
     * Update user's role in conversation.
     *
     * @param Conversation $conversation
     * @param int $userId
     * @param string $role
     * @return void
     */
    public function updateUserRole(Conversation $conversation, int $userId, string $role): void;
    
    /**
     * Update last read timestamp for a user.
     *
     * @param Conversation $conversation
     * @param int $userId
     * @return void
     */
    public function markAsRead(Conversation $conversation, int $userId): void;
    
    /**
     * Find or create a direct conversation between two users.
     *
     * @param int $workspaceId
     * @param int $user1Id
     * @param int $user2Id
     * @return Conversation
     */
    public function findOrCreateDirectConversation(int $workspaceId, int $user1Id, int $user2Id): Conversation;
}
