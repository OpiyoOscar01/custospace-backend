<?php

namespace App\Services;

use App\Models\Mention;
use App\Repositories\Contracts\MentionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MentionService
{
    /**
     * @var MentionRepositoryInterface
     */
    protected $mentionRepository;
    
    /**
     * MentionService constructor.
     * 
     * @param MentionRepositoryInterface $mentionRepository
     */
    public function __construct(MentionRepositoryInterface $mentionRepository)
    {
        $this->mentionRepository = $mentionRepository;
    }
    
    /**
     * Get paginated mentions for a user.
     * 
     * @param int $userId
     * @param int $perPage
     * @param array $criteria
     * @return LengthAwarePaginator
     */
    public function getPaginatedMentionsForUser(int $userId, int $perPage = 15, array $criteria = []): LengthAwarePaginator
    {
        return $this->mentionRepository->getPaginatedMentionsForUser($userId, $perPage, $criteria);
    }
    
    /**
     * Get unread mentions count for a user.
     * 
     * @param int $userId
     * @return int
     */
    public function getUnreadMentionsCountForUser(int $userId): int
    {
        return $this->mentionRepository->getUnreadMentionsCountForUser($userId);
    }
    
    /**
     * Get a mention by ID.
     * 
     * @param int $id
     * @return Mention|null
     */
    public function getMentionById(int $id): ?Mention
    {
        return $this->mentionRepository->getMentionById($id);
    }
    
    /**
     * Mark a mention as read.
     * 
     * @param Mention $mention
     * @return Mention
     */
    public function markAsRead(Mention $mention): Mention
    {
        return $this->mentionRepository->markAsRead($mention);
    }
    
    /**
     * Mark all mentions as read for a user.
     * 
     * @param int $userId
     * @return int Number of mentions updated
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->mentionRepository->markAllAsRead($userId);
    }
    
    /**
     * Delete a mention.
     * 
     * @param Mention $mention
     * @return bool
     */
    public function deleteMention(Mention $mention): bool
    {
        return $this->mentionRepository->deleteMention($mention);
    }
}
