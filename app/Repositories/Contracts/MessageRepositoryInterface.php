<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    /**
     * Get paginated messages for a conversation.
     *
     * @param int $conversationId
     * @param int $perPage
     * @param array $criteria
     * @return LengthAwarePaginator
     */
    public function getPaginatedMessages(int $conversationId, int $perPage = 50, array $criteria = []): LengthAwarePaginator;
    
    /**
     * Get messages after a specific timestamp.
     *
     * @param int $conversationId
     * @param string $timestamp
     * @return Collection
     */
    public function getMessagesAfter(int $conversationId, string $timestamp): Collection;
    
    /**
     * Get a message by ID.
     *
     * @param int $id
     * @return Message|null
     */
    public function getMessageById(int $id): ?Message;
    
    /**
     * Create a new message.
     *
     * @param array $data
     * @return Message
     */
    public function createMessage(array $data): Message;
    
    /**
     * Update an existing message.
     *
     * @param Message $message
     * @param array $data
     * @return Message
     */
    public function updateMessage(Message $message, array $data): Message;
    
    /**
     * Delete a message.
     *
     * @param Message $message
     * @return bool
     */
    public function deleteMessage(Message $message): bool;
}
