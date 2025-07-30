<?php

namespace App\Repositories;

use App\Models\TimeLog;
use App\Repositories\Contracts\TimeLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TimeLogRepository
 * 
 * Handles data access operations for time logs
 */
class TimeLogRepository implements TimeLogRepositoryInterface
{
    /**
     * Get all time logs with optional filters and pagination.
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = TimeLog::with(['user', 'task']);

        $query = $this->applyFilters($query, $filters);

        return $query->orderBy('started_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new time log.
     */
    public function create(array $data): TimeLog
    {
        return TimeLog::create($data);
    }

    /**
     * Update an existing time log.
     */
    public function update(TimeLog $timeLog, array $data): TimeLog
    {
        $timeLog->update($data);
        return $timeLog->fresh(['user', 'task']);
    }

    /**
     * Delete a time log.
     */
    public function delete(TimeLog $timeLog): bool
    {
        return $timeLog->delete();
    }

    /**
     * Find a running time log for a specific user.
     */
    public function findRunningLogForUser(int $userId): ?TimeLog
    {
        return TimeLog::where('user_id', $userId)
                     ->whereNull('ended_at')
                     ->first();
    }

    /**
     * Get running time logs for a user.
     */
    public function getRunningLogsForUser(int $userId): Collection
    {
        return TimeLog::with(['task'])
                     ->where('user_id', $userId)
                     ->whereNull('ended_at')
                     ->orderBy('started_at', 'desc')
                     ->get();
    }

    /**
     * Get time logs for summary calculations.
     */
    public function getForSummary(array $filters = []): Collection
    {
        $query = TimeLog::query();
        $query = $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Get time logs by task ID.
     */
    public function getByTaskId(int $taskId): Collection
    {
        return TimeLog::with(['user'])
                     ->where('task_id', $taskId)
                     ->orderBy('started_at', 'desc')
                     ->get();
    }

    /**
     * Get billable time logs.
     */
    public function getBillableTimeLogs(array $filters = []): Collection
    {
        $filters['is_billable'] = true;
        $query = TimeLog::with(['user', 'task']);
        $query = $this->applyFilters($query, $filters);
        
        return $query->orderBy('started_at', 'desc')->get();
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by task
        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('started_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('started_at', '<=', $filters['end_date']);
        }

        // Filter by billable status
        if (isset($filters['is_billable'])) {
            $query->where('is_billable', $filters['is_billable']);
        }

        // Filter by running status
        if (isset($filters['is_running'])) {
            if ($filters['is_running']) {
                $query->whereNull('ended_at');
            } else {
                $query->whereNotNull('ended_at');
            }
        }

        // Search in description
        if (!empty($filters['search'])) {
            $query->where('description', 'like', '%' . $filters['search'] . '%');
        }

        return $query;
    }
}
