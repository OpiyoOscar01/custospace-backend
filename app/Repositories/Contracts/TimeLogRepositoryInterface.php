<?php

namespace App\Repositories\Contracts;

use App\Models\TimeLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface TimeLogRepositoryInterface
 * 
 * Contract for time log repository operations
 */
interface TimeLogRepositoryInterface
{
    /**
     * Get all time logs with optional filters and pagination.
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator;

    /**
     * Create a new time log.
     */
    public function create(array $data): TimeLog;

    /**
     * Update an existing time log.
     */
    public function update(TimeLog $timeLog, array $data): TimeLog;

    /**
     * Delete a time log.
     */
    public function delete(TimeLog $timeLog): bool;

    /**
     * Find a running time log for a specific user.
     */
    public function findRunningLogForUser(int $userId): ?TimeLog;

    /**
     * Get running time logs for a user.
     */
    public function getRunningLogsForUser(int $userId): Collection;

    /**
     * Get time logs for summary calculations.
     */
    public function getForSummary(array $filters = []): Collection;

    /**
     * Get time logs by task ID.
     */
    public function getByTaskId(int $taskId): Collection;

    /**
     * Get billable time logs.
     */
    public function getBillableTimeLogs(array $filters = []): Collection;
}
