<?php

namespace App\Repositories;

use App\Models\Mention;
use App\Repositories\Contracts\MentionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MentionRepository implements MentionRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getPaginatedMentionsForUser(int $userId, int $perPage = 15, array $criteria = []): LengthAwarePaginator
    {
        $query = Mention::where('user_id', $userId);
        
        if (isset($criteria['is_read'])) {
            $query->where('is_read', $criteria['is_read']);
        }
        
        if (isset($criteria['mentionable_type'])) {
            $query->where('mentionable_type', $criteria['mentionable_type']);
        }
        
        return $query->with([
                'mentionedBy',
                'mentionable.user',
                'mentionable' // Load the mentionable model (Comment or Message)
            ])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getUnreadMentionsCountForUser(int $userId): int
    {
        return Mention::where('user_id', $userId)
                      ->where('is_read', false)
                      ->count();
    }

    /**
     * @inheritDoc
     */
    public function getMentionById(int $id): ?Mention
    {
        return Mention::with([
            'mentionedBy',
            'mentionable.user',
            'mentionable'
        ])->find($id);
    }

    /**
     * @inheritDoc
     */
    public function createMention(array $data): Mention
    {
        return Mention::create($data);
    }

    /**
     * @inheritDoc
     */
    public function markAsRead(Mention $mention): Mention
    {
        $mention->update(['is_read' => true]);
        
        return $mention->fresh();
    }

    /**
     * @inheritDoc
     */
    public function markAllAsRead(int $userId): int
    {
        return Mention::where('user_id', $userId)
                     ->where('is_read', false)
                     ->update(['is_read' => true]);
    }

    /**
     * @inheritDoc
     */
    public function deleteMention(Mention $mention): bool
    {
        return $mention->delete();
    }
}
