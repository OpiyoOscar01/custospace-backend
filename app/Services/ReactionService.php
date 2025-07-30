<?php
// app/Services/ReactionService.php

namespace App\Services;

use App\Models\Reaction;
use App\Repositories\Contracts\ReactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Reaction Service
 * 
 * Handles business logic for reaction operations
 */
class ReactionService
{
    public function __construct(
        private ReactionRepositoryInterface $reactionRepository
    ) {}

    /**
     * Get paginated reactions with filters.
     */
    public function getPaginatedReactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->reactionRepository->getPaginated($filters, $perPage);
    }

    /**
     * Create a new reaction.
     */
    public function createReaction(array $data): Reaction
    {
        // Validate reaction type
        if (!Reaction::isValidType($data['type'])) {
            throw new InvalidArgumentException("Invalid reaction type: {$data['type']}");
        }

        // Add current user if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        // Check if user already has a reaction to this item
        $existingReaction = $this->reactionRepository->findUserReaction(
            $data['user_id'],
            $data['reactable_type'],
            $data['reactable_id']
        );

        if ($existingReaction) {
            throw new InvalidArgumentException('User already has a reaction to this item. Use toggle or update instead.');
        }

        return $this->reactionRepository->create($data);
    }

    /**
     * Get reaction by ID.
     */
    public function getById(int $id): ?Reaction
    {
        return $this->reactionRepository->findById($id);
    }

    /**
     * Update an existing reaction.
     */
    public function updateReaction(Reaction $reaction, array $data): Reaction
    {
        // Validate reaction type if being updated
        if (isset($data['type']) && !Reaction::isValidType($data['type'])) {
            throw new InvalidArgumentException("Invalid reaction type: {$data['type']}");
        }

        return $this->reactionRepository->update($reaction, $data);
    }

    /**
     * Delete a reaction.
     */
    public function deleteReaction(Reaction $reaction): bool
    {
        return $this->reactionRepository->delete($reaction);
    }

    /**
     * Toggle user's reaction to an item.
     */
    public function toggleReaction(
        string $reactableType,
        int $reactableId,
        string $type,
        ?int $userId = null
    ): ?Reaction {
        // Validate reaction type
        if (!Reaction::isValidType($type)) {
            throw new InvalidArgumentException("Invalid reaction type: {$type}");
        }

        $userId = $userId ?? Auth::id();

        return $this->reactionRepository->toggleReaction($userId, $reactableType, $reactableId, $type);
    }

    /**
     * Get user's reaction to a specific item.
     */
    public function getUserReaction(
        string $reactableType,
        int $reactableId,
        ?int $userId = null
    ): ?Reaction {
        $userId = $userId ?? Auth::id();
        
        return $this->reactionRepository->findUserReaction($userId, $reactableType, $reactableId);
    }

    /**
     * Get all reactions for a specific item.
     */
    public function getItemReactions(string $reactableType, int $reactableId): Collection
    {
        return $this->reactionRepository->getByReactable($reactableType, $reactableId);
    }

    /**
     * Get reaction counts for a specific item.
     */
    public function getReactionCounts(string $reactableType, int $reactableId): array
    {
        return $this->reactionRepository->getReactionCounts($reactableType, $reactableId);
    }

    /**
     * Get reaction summary for a specific item.
     */
    public function getReactionSummary(string $reactableType, int $reactableId, ?int $userId = null): array
    {
        $counts = $this->getReactionCounts($reactableType, $reactableId);
        $userReaction = null;

        if ($userId) {
            $userReaction = $this->getUserReaction($reactableType, $reactableId, $userId);
        }

        return [
            'counts' => $counts,
            'total' => array_sum($counts),
            'user_reaction' => $userReaction?->type,
            'available_types' => Reaction::TYPES,
        ];
    }

    /**
     * Get user's reactions.
     */
    public function getUserReactions(?int $userId = null, array $filters = []): Collection
    {
        $userId = $userId ?? Auth::id();
        
        return $this->reactionRepository->getUserReactions($userId, $filters);
    }

    /**
     * Bulk toggle reactions for multiple items.
     */
    public function bulkToggleReactions(array $items, string $type, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        $results = [];

        foreach ($items as $item) {
            $results[] = [
                'reactable_type' => $item['reactable_type'],
                'reactable_id' => $item['reactable_id'],
                'reaction' => $this->toggleReaction(
                    $item['reactable_type'],
                    $item['reactable_id'],
                    $type,
                    $userId
                ),
            ];
        }

        return $results;
    }

    /**
     * Get available reaction types.
     */
    public function getAvailableTypes(): array
    {
        return Reaction::TYPES;
    }
}