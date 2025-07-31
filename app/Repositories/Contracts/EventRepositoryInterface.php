<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Event Repository Interface
 * 
 * Defines contract for event data access operations
 */
interface EventRepositoryInterface
{
    /**
     * Get paginated events for a workspace
     */
    public function getByWorkspace(int $workspaceId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events by date range
     */
    public function getByDateRange(int $workspaceId, string $startDate, string $endDate): Collection;

    /**
     * Get events by type
     */
    public function getByType(int $workspaceId, string $type): Collection;

    /**
     * Get user's events
     */
    public function getUserEvents(int $userId, array $filters = []): Collection;

    /**
     * Create a new event
     */
    public function create(array $data): Event;

    /**
     * Update an event
     */
    public function update(Event $event, array $data): Event;

    /**
     * Delete an event
     */
    public function delete(Event $event): bool;

    /**
     * Find event by ID with relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Event;

    /**
     * Get upcoming events
     */
    public function getUpcoming(int $workspaceId, int $limit = 10): Collection;
}