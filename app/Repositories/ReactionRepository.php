<?php
// app/Repositories/ReactionRepository.php

namespace App\Repositories;

use App\Models\Reaction;
use App\Repositories\Contracts\ReactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reaction Repository Implementation
 * 
 * Handles all database operations for reactions
 */
class ReactionRepository implements ReactionRepositoryInterface
{
    /**
     * Get paginated reactions with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reaction::with(['user', 'reactable']);

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['reactable_type'])) {
            $query->where('reactable_type', $filters['reactable_type']);
        }

        if (!empty($filters['reactable_id'])) {
            $query->where('reactable_id', $filters['reactable_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new reaction.
     */
    public function create(array $data): Reaction
    {
        return Reaction::create($data);
    }

    /**
     * Find reaction by ID.
     */
    public function findById(int $id): ?Reaction
    {
        return Reaction::with(['user', 'reactable'])->find($id);
    }

    /**
     * Update existing reaction.
     */
    public function update(Reaction $reaction, array $data): Reaction
    {
        $reaction->update($data);
        return $reaction->fresh();
    }

    /**
     * Delete reaction.
     */
    public function delete(Reaction $reaction): bool
    {
        return $reaction->delete();
    }

    /**
     * Find user's reaction to a specific reactable.
     */
    public function findUserReaction(int $userId, string $reactableType, int $reactableId): ?Reaction
    {
        return Reaction::where('user_id', $userId)
            ->where('reactable_type', $reactableType)
            ->where('reactable_id', $reactableId)
            ->first();
    }

    /**
     * Get reactions for a specific reactable.
     */
    public function getByReactable(string $reactableType, int $reactableId): Collection
    {
        return Reaction::where('reactable_type', $reactableType)
            ->where('reactable_id', $reactableId)
            ->with(['user'])
            ->get();
    }

    /**
     * Get reaction counts for a specific reactable.
     */
    public function getReactionCounts(string $reactableType, int $reactableId): array
    {
        return Reaction::where('reactable_type', $reactableType)
            ->where('reactable_id', $reactableId)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Toggle user reaction (add if not exists, remove if exists, or update type).
     */
    public function toggleReaction(int $userId, string $reactableType, int $reactableId, string $type): ?Reaction
    {
        $existingReaction = $this->findUserReaction($userId, $reactableType, $reactableId);

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                // Same reaction type - remove it
                $existingReaction->delete();
                return null;
            } else {
                // Different reaction type - update it
                return $this->update($existingReaction, ['type' => $type]);
            }
        } else {
            // No existing reaction - create new one
            return $this->create([
                'user_id' => $userId,
                'reactable_type' => $reactableType,
                'reactable_id' => $reactableId,
                'type' => $type,
            ]);
        }
    }

    /**
     * Get user's reactions.
     */
    public function getUserReactions(int $userId, array $filters = []): Collection
    {
        $query = Reaction::where('user_id', $userId)->with(['reactable']);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['reactable_type'])) {
            $query->where('reactable_type', $filters['reactable_type']);
        }

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}