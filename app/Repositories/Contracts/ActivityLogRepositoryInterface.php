<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Activity Log Repository Interface
 * 
 * Defines the contract for activity log data operations
 */
interface ActivityLogRepositoryInterface
{
    /**
     * Get paginated activity logs with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new activity log entry.
     */
    public function create(array $data): ActivityLog;

    /**
     * Find activity log by ID.
     */
    public function findById(int $id): ?ActivityLog;

    /**
     * Get activity logs by workspace.
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection;

    /**
     * Get activity logs by user.
     */
    public function getByUser(int $userId, array $filters = []): Collection;

    /**
     * Get activity logs by action type.
     */
    public function getByAction(string $action, array $filters = []): Collection;

    /**
     * Get activity logs for a specific subject.
     */
    public function getBySubject(string $subjectType, int $subjectId): Collection;

    /**
     * Delete activity logs older than specified days.
     */
    public function deleteOlderThan(int $days): int;

    /**
     * Get activity statistics.
     */
    public function getStatistics(array $filters = []): array;
}