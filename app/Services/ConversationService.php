<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    /**
     * @var ConversationRepositoryInterface
     */
    protected $conversationRepository;
    
    /**
     * ConversationService constructor.
     * 
     * @param ConversationRepositoryInterface $conversationRepository
     */
    public function __construct(ConversationRepositoryInterface $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }
    
    /**
     * Get paginated conversations.
     * 
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedConversations(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->conversationRepository->getPaginatedConversations($criteria, $perPage);
    }
    
    /**
     * Get all conversations for a user.
     * 
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getConversationsForUser(int $userId, array $filters = []): Collection
    {
        return $this->conversationRepository->getConversationsForUser($userId, $filters);
    }
    
    /**
     * Get a conversation by ID.
     * 
     * @param int $id
     * @return Conversation|null
     */
    public function getConversationById(int $id): ?Conversation
    {
        return $this->conversationRepository->getConversationById($id);
    }
    
    /**
     * Create a new conversation.
     * 
     * @param array $data
     * @return Conversation
     */
    public function createConversation(array $data): Conversation
    {
        return DB::transaction(function () use ($data) {
            return $this->conversationRepository->createConversation($data);
        });
    }
    
    /**
     * Update an existing conversation.
     * 
     * @param Conversation $conversation
     * @param array $data
     * @return Conversation
     */
    public function updateConversation(Conversation $conversation, array $data): Conversation
    {
        return $this->conversationRepository->updateConversation($conversation, $data);
    }
    
    /**
     * Delete a conversation.
     * 
     * @param Conversation $conversation
     * @return bool
     */
    public function deleteConversation(Conversation $conversation): bool
    {
        return DB::transaction(function () use ($conversation) {
            // This will cascade delete messages and conversation_user entries
            return $this->conversationRepository->deleteConversation($conversation);
        });
    }
    
    /**
     * Add users to a conversation.
     * 
     * @param Conversation $conversation
     * @param array $userIds
     * @param string $role
     * @return void
     */
    public function addUsers(Conversation $conversation, array $userIds, string $role = 'member'): void
    {
        $this->conversationRepository->addUsers($conversation, $userIds, $role);
    }
    
    /**
     * Remove users from a conversation.
     * 
     * @param Conversation $conversation
     * @param array $userIds
     * @return void
     */
    public function removeUsers(Conversation $conversation, array $userIds): void
    {
        $this->conversationRepository->removeUsers($conversation, $userIds);
    }
    
    /**
     * Update user's role in conversation.
     * 
     * @param Conversation $conversation
     * @param int $userId
     * @param string $role
     * @return void
     */
    public function updateUserRole(Conversation $conversation, int $userId, string $role): void
    {
        $this->conversationRepository->updateUserRole($conversation, $userId, $role);
    }
    
    /**
     * Mark conversation as read for a user.
     * 
     * @param Conversation $conversation
     * @param int $userId
     * @return void
     */
    public function markAsRead(Conversation $conversation, int $userId): void
    {
        $this->conversationRepository->markAsRead($conversation, $userId);
    }
    
    /**
     * Find or create a direct conversation between two users.
     * 
     * @param int $workspaceId
     * @param int $user1Id
     * @param int $user2Id
     * @return Conversation
     */
    public function findOrCreateDirectConversation(int $workspaceId, int $user1Id, int $user2Id): Conversation
    {
        return $this->conversationRepository->findOrCreateDirectConversation($workspaceId, $user1Id, $user2Id);
    }
}
