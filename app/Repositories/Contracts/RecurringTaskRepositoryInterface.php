<?php

namespace App\Repositories\Contracts;

use App\Models\RecurringTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface RecurringTaskRepositoryInterface
 * 
 * Contract for recurring task repository operations
 */
interface RecurringTaskRepositoryInterface
{
    /**
     * Get all recurring tasks with optional filters and pagination.
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator;

    /**
     * Create a new recurring task.
     */
    public function create(array $data): RecurringTask;

    /**
     * Update an existing recurring task.
     */
    public function update(RecurringTask $recurringTask, array $data): RecurringTask;

    /**
     * Delete a recurring task.
     */
    public function delete(RecurringTask $recurringTask): bool;

    /**
     * Get due recurring tasks.
     */
    public function getDueRecurringTasks(): Collection;

    /**
     * Get recurring tasks by frequency.
     */
    public function getByFrequency(string $frequency): Collection;

    /**
     * Get active recurring tasks count.
     */
    public function getActiveCount(): int;

    /**
     * Get total recurring tasks count.
     */
    public function getTotalCount(): int;

    /**
     * Get due recurring tasks count.
     */
    public function getDueCount(): int;

    /**
     * Get count by frequency.
     */
    public function getCountByFrequency(string $frequency): int;
}
