<?php

namespace App\Repositories;

use App\Models\RecurringTask;
use App\Repositories\Contracts\RecurringTaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class RecurringTaskRepository
 * 
 * Handles data access operations for recurring tasks
 */
class RecurringTaskRepository implements RecurringTaskRepositoryInterface
{
    /**
     * Get all recurring tasks with optional filters and pagination.
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = RecurringTask::with(['task']);

        $query = $this->applyFilters($query, $filters);

        return $query->orderBy('next_due_date', 'asc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new recurring task.
     */
    public function create(array $data): RecurringTask
    {
        return RecurringTask::create($data);
    }

    /**
     * Update an existing recurring task.
     */
    public function update(RecurringTask $recurringTask, array $data): RecurringTask
    {
        $recurringTask->update($data);
        return $recurringTask->fresh(['task']);
    }

    /**
     * Delete a recurring task.
     */
    public function delete(RecurringTask $recurringTask): bool
    {
        return $recurringTask->delete();
    }

    /**
     * Get due recurring tasks.
     */
    public function getDueRecurringTasks(): Collection
    {
        return RecurringTask::with(['task'])
                          ->due()
                          ->get();
    }

    /**
     * Get recurring tasks by frequency.
     */
    public function getByFrequency(string $frequency): Collection
    {
        return RecurringTask::with(['task'])
                          ->byFrequency($frequency)
                          ->orderBy('next_due_date', 'asc')
                          ->get();
    }

    /**
     * Get active recurring tasks count.
     */
    public function getActiveCount(): int
    {
        return RecurringTask::active()->count();
    }

    /**
     * Get total recurring tasks count.
     */
    public function getTotalCount(): int
    {
        return RecurringTask::count();
    }

    /**
     * Get due recurring tasks count.
     */
    public function getDueCount(): int
    {
        return RecurringTask::due()->count();
    }

    /**
     * Get count by frequency.
     */
    public function getCountByFrequency(string $frequency): int
    {
        return RecurringTask::byFrequency($frequency)->count();
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by task
        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        // Filter by frequency
        if (!empty($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by due status
        if (isset($filters['is_due'])) {
            if ($filters['is_due']) {
                $query->due();
            } else {
                $query->where('next_due_date', '>', now());
            }
        }

        // Filter by next due date range
        if (!empty($filters['due_date_from'])) {
            $query->whereDate('next_due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->whereDate('next_due_date', '<=', $filters['due_date_to']);
        }

        return $query;
    }
}
