<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MessageRepository implements MessageRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getPaginatedMessages(int $conversationId, int $perPage = 50, array $criteria = []): LengthAwarePaginator
    {
        $query = Message::where('conversation_id', $conversationId);
        
        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }
        
        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }
        
        // Sort by created_at in descending order (newest first)
        // then reverse the collection to display correctly (oldest first)
        return $query->with(['user'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage)
                    ->setCollection(
                        $query->latest()->paginate($perPage)->getCollection()->reverse()
                    );
    }

    /**
     * @inheritDoc
     */
    public function getMessagesAfter(int $conversationId, string $timestamp): Collection
    {
        return Message::where('conversation_id', $conversationId)
                     ->where('created_at', '>', $timestamp)
                     ->with(['user'])
                     ->orderBy('created_at', 'asc')
                     ->get();
    }

    /**
     * @inheritDoc
     */
    public function getMessageById(int $id): ?Message
    {
        return Message::with(['user'])->find($id);
    }

    /**
     * @inheritDoc
     */
    public function createMessage(array $data): Message
    {
        // Add user_id from authenticated user if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }
        
        // Set default type if not provided
        if (!isset($data['type'])) {
            $data['type'] = 'text';
        }
        
        return Message::create($data);
    }

    /**
     * @inheritDoc
     */
    public function updateMessage(Message $message, array $data): Message
    {
        // Set edited flags
        $data['is_edited'] = true;
        $data['edited_at'] = Carbon::now();
        
        $message->update($data);
        
        return $message->fresh();
    }

    /**
     * @inheritDoc
     */
    public function deleteMessage(Message $message): bool
    {
        return $message->delete();
    }
}
