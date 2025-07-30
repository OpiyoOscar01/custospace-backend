<?php

namespace App\Services;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\ConversationService;

class MessageService
{
    /**
     * @var MessageRepositoryInterface
     */
    protected $messageRepository;
    
    /**
     * @var ConversationService
     */
    protected $conversationService;
    
    /**
     * MessageService constructor.
     * 
     * @param MessageRepositoryInterface $messageRepository
     * @param ConversationService $conversationService
     */
    public function __construct(
        MessageRepositoryInterface $messageRepository,
        ConversationService $conversationService
    ) {
        $this->messageRepository = $messageRepository;
        $this->conversationService = $conversationService;
    }
    
    /**
     * Get paginated messages for a conversation.
     * 
     * @param int $conversationId
     * @param int $perPage
     * @param array $criteria
     * @return LengthAwarePaginator
     */
    public function getPaginatedMessages(int $conversationId, int $perPage = 50, array $criteria = []): LengthAwarePaginator
    {
        return $this->messageRepository->getPaginatedMessages($conversationId, $perPage, $criteria);
    }
    
    /**
     * Get messages after a specific timestamp.
     * 
     * @param int $conversationId
     * @param string $timestamp
     * @return Collection
     */
    public function getMessagesAfter(int $conversationId, string $timestamp): Collection
    {
        return $this->messageRepository->getMessagesAfter($conversationId, $timestamp);
    }
    
    /**
     * Get a message by ID.
     * 
     * @param int $id
     * @return Message|null
     */
    public function getMessageById(int $id): ?Message
    {
        return $this->messageRepository->getMessageById($id);
    }
    
    /**
     * Create a new message.
     * 
     * @param array $data
     * @return Message
     */
    public function createMessage(array $data): Message
    {
        return DB::transaction(function () use ($data) {
            $message = $this->messageRepository->createMessage($data);
            
            // Update the conversation's last activity timestamp
            $conversation = $message->conversation;
            $conversation->touch();
            
            // Process mentions
            $this->processMentions($message);
            
            return $message->fresh(['user']);
        });
    }
    
    /**
     * Update an existing message.
     * 
     * @param Message $message
     * @param array $data
     * @return Message
     */
    public function updateMessage(Message $message, array $data): Message
    {
        return DB::transaction(function () use ($message, $data) {
            $updatedMessage = $this->messageRepository->updateMessage($message, $data);
            
            // If content changed, update mentions
            if (isset($data['content'])) {
                // Clear existing mentions
                $message->mentions()->delete();
                
                // Process new mentions
                $this->processMentions($updatedMessage);
            }
            
            return $updatedMessage;
        });
    }
    
    /**
     * Delete a message.
     * 
     * @param Message $message
     * @return bool
     */
    public function deleteMessage(Message $message): bool
    {
        return DB::transaction(function () use ($message) {
            // Delete all mentions associated with this message
            $message->mentions()->delete();
            
            // Delete the message
            return $this->messageRepository->deleteMessage($message);
        });
    }
    
    /**
     * Process and create mentions from message content.
     * 
     * @param Message $message
     * @return void
     */
    protected function processMentions(Message $message): void
    {
        // Only process mentions for text messages
        if ($message->type !== 'text') {
            return;
        }
        
        // Simple regex to find @username mentions
        // In a real app, you might want a more sophisticated approach
        preg_match_all('/@([a-zA-Z0-9_]+)/', $message->content, $matches);
        
        if (empty($matches[1])) {
            return;
        }
        
        $usernames = $matches[1];
        
        // Find mentioned users who are also in this conversation
        $users = \App\Models\User::whereIn('username', $usernames)
            ->whereHas('conversations', function($query) use ($message) {
                $query->where('conversations.id', $message->conversation_id);
            })
            ->get();
        
        foreach ($users as $user) {
            // Don't create mentions for the message author
            if ($user->id === $message->user_id) {
                continue;
            }
            
            $message->mentions()->create([
                'user_id' => $user->id,
                'mentioned_by_id' => $message->user_id,
            ]);
        }
    }
}
