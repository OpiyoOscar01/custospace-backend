<?php

namespace App\Repositories\Contracts;

use App\Models\Mention;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MentionRepositoryInterface
{
    /**
     * Get paginated mentions for a user.
     *
     * @param int $userId
     * @param int $perPage
     * @param array $criteria
     * @return LengthAwarePaginator
     */
    public function getPaginatedMentionsForUser(int $userId, int $perPage = 15, array $criteria = []): LengthAwarePaginator;
    
    /**
     * Get unread mentions count for a user.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadMentionsCountForUser(int $userId): int;
    
    /**
     * Get a mention by ID.
     *
     * @param int $id
     * @return Mention|null
     */
    public function getMentionById(int $id): ?Mention;
    
    /**
     * Create a new mention.
     *
     * @param array $data
     * @return Mention
     */
    public function createMention(array $data): Mention;
    
    /**
     * Mark a mention as read.
     *
     * @param Mention $mention
     * @return Mention
     */
    public function markAsRead(Mention $mention): Mention;
    
    /**
     * Mark all mentions as read for a user.
     *
     * @param int $userId
     * @return int Number of mentions updated
     */
    public function markAllAsRead(int $userId): int;
    
    /**
     * Delete a mention.
     *
     * @param Mention $mention
     * @return bool
     */
    public function deleteMention(Mention $mention): bool;
}
