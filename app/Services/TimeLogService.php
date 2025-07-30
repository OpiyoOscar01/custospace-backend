<?php

namespace App\Services;

use App\Models\TimeLog;
use App\Repositories\Contracts\TimeLogRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TimeLogService
 * 
 * Handles business logic for time log operations
 */
class TimeLogService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected TimeLogRepositoryInterface $timeLogRepository
    ) {}

    /**
     * Get all time logs with optional filtering and pagination.
     */
    public function getAllTimeLogs(array $filters = []): LengthAwarePaginator
    {
        return $this->timeLogRepository->getAllWithFilters($filters);
    }

    /**
     * Create a new time log.
     */
    public function createTimeLog(array $data): TimeLog
    {
        // If no end time provided, this is a running time log
        if (empty($data['ended_at'])) {
            $data['ended_at'] = null;
            $data['duration'] = null;
        } else {
            // Calculate duration if both start and end times are provided
            $startTime = Carbon::parse($data['started_at']);
            $endTime = Carbon::parse($data['ended_at']);
            $data['duration'] = $startTime->diffInMinutes($endTime);
        }

        return $this->timeLogRepository->create($data);
    }

    /**
     * Update an existing time log.
     */
    public function updateTimeLog(TimeLog $timeLog, array $data): TimeLog
    {
        // Recalculate duration if times are updated
        if (isset($data['started_at']) || isset($data['ended_at'])) {
            $startTime = Carbon::parse($data['started_at'] ?? $timeLog->started_at);
            $endTime = isset($data['ended_at']) ? Carbon::parse($data['ended_at']) : $timeLog->ended_at;
            
            if ($endTime) {
                $data['duration'] = $startTime->diffInMinutes($endTime);
            } else {
                $data['duration'] = null;
            }
        }

        return $this->timeLogRepository->update($timeLog, $data);
    }

    /**
     * Delete a time log.
     */
    public function deleteTimeLog(TimeLog $timeLog): bool
    {
        return $this->timeLogRepository->delete($timeLog);
    }

    /**
     * Start a new time log for a user.
     */
    public function startTimeLog(array $data): TimeLog
    {
        // Check if user already has a running time log
        $existingRunningLog = $this->timeLogRepository->findRunningLogForUser($data['user_id']);
        
        if ($existingRunningLog) {
            throw new \InvalidArgumentException('User already has a running time log. Please stop it first.');
        }

        $data['started_at'] = $data['started_at'] ?? now();
        $data['ended_at'] = null;
        $data['duration'] = null;

        return $this->timeLogRepository->create($data);
    }

    /**
     * Stop a running time log.
     */
    public function stopTimeLog(TimeLog $timeLog, ?string $endedAt = null): TimeLog
    {
        if (!$timeLog->isRunning()) {
            throw new \InvalidArgumentException('Time log is already stopped.');
        }

        $endTime = $endedAt ? Carbon::parse($endedAt) : now();
        $duration = $timeLog->started_at->diffInMinutes($endTime);

        return $this->timeLogRepository->update($timeLog, [
            'ended_at' => $endTime,
            'duration' => $duration,
        ]);
    }

    /**
     * Get time logs summary/statistics.
     */
    public function getTimeLogsSummary(array $filters = []): array
    {
        $timeLogs = $this->timeLogRepository->getForSummary($filters);

        $totalMinutes = $timeLogs->sum('duration') ?? 0;
        $billableMinutes = $timeLogs->where('is_billable', true)->sum('duration') ?? 0;
        $totalEarnings = $timeLogs->where('is_billable', true)->sum(function ($log) {
            return ($log->duration / 60) * ($log->hourly_rate ?? 0);
        });

        return [
            'total_time_logs' => $timeLogs->count(),
            'total_hours' => round($totalMinutes / 60, 2),
            'billable_hours' => round($billableMinutes / 60, 2),
            'non_billable_hours' => round(($totalMinutes - $billableMinutes) / 60, 2),
            'total_earnings' => round($totalEarnings, 2),
            'average_hourly_rate' => $timeLogs->where('is_billable', true)->avg('hourly_rate') ?? 0,
            'running_logs_count' => $timeLogs->whereNull('ended_at')->count(),
        ];
    }

    /**
     * Get billable time logs for invoicing.
     */
    public function getBillableTimeLogs(array $filters = []): LengthAwarePaginator
    {
        $filters['is_billable'] = true;
        return $this->timeLogRepository->getAllWithFilters($filters);
    }

    /**
     * Get running time logs for a user.
     */
    public function getRunningTimeLogsForUser(int $userId): Collection
    {
        return $this->timeLogRepository->getRunningLogsForUser($userId);
    }

    /**
     * Get time logs for a specific task.
     */
    public function getTimeLogsForTask(int $taskId): Collection
    {
        return $this->timeLogRepository->getByTaskId($taskId);
    }

    /**
     * Calculate total earnings for a user within a date range.
     */
    public function calculateUserEarnings(int $userId, ?string $startDate = null, ?string $endDate = null): float
    {
        $filters = [
            'user_id' => $userId,
            'is_billable' => true,
        ];

        if ($startDate) {
            $filters['start_date'] = $startDate;
        }

        if ($endDate) {
            $filters['end_date'] = $endDate;
        }

        $timeLogs = $this->timeLogRepository->getForSummary($filters);

        return $timeLogs->sum(function ($log) {
            return ($log->duration / 60) * ($log->hourly_rate ?? 0);
        });
    }
}
