<?php
// app/Repositories/Contracts/ReactionRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\Reaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reaction Repository Interface
 * 
 * Defines the contract for reaction data operations
 */
interface ReactionRepositoryInterface
{
    /**
     * Get paginated reactions with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new reaction.
     */
    public function create(array $data): Reaction;

    /**
     * Find reaction by ID.
     */
    public function findById(int $id): ?Reaction;

    /**
     * Update existing reaction.
     */
    public function update(Reaction $reaction, array $data): Reaction;

    /**
     * Delete reaction.
     */
    public function delete(Reaction $reaction): bool;

    /**
     * Find user's reaction to a specific reactable.
     */
    public function findUserReaction(int $userId, string $reactableType, int $reactableId): ?Reaction;

    /**
     * Get reactions for a specific reactable.
     */
    public function getByReactable(string $reactableType, int $reactableId): Collection;

    /**
     * Get reaction counts for a specific reactable.
     */
    public function getReactionCounts(string $reactableType, int $reactableId): array;

    /**
     * Toggle user reaction (add if not exists, remove if exists, or update type).
     */
    public function toggleReaction(int $userId, string $reactableType, int $reactableId, string $type): ?Reaction;

    /**
     * Get user's reactions.
     */
    public function getUserReactions(int $userId, array $filters = []): Collection;
}