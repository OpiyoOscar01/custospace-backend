<?php
// app/Services/ActivityLogService.php

namespace App\Services;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Activity Log Service
 * 
 * Handles business logic for activity logging operations
 */
class ActivityLogService
{
    public function __construct(
        private ActivityLogRepositoryInterface $activityLogRepository
    ) {}

    /**
     * Get paginated activity logs with filters.
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activityLogRepository->getPaginated($filters, $perPage);
    }

    /**
     * Log a new activity.
     */
    public function logActivity(array $data): ActivityLog
    {
        // Add current user and request information if not provided
        $data = array_merge([
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $data);

        return $this->activityLogRepository->create($data);
    }

    /**
     * Log activity for a specific subject.
     */
    public function logSubjectActivity(
        string $action,
        string $description,
        int $workspaceId,
        $subject,
        ?array $properties = null
    ): ActivityLog {
        return $this->logActivity([
            'action' => $action,
            'description' => $description,
            'workspace_id' => $workspaceId,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'properties' => $properties,
        ]);
    }

    /**
     * Get activity log by ID.
     */
    public function getById(int $id): ?ActivityLog
    {
        return $this->activityLogRepository->findById($id);
    }

    /**
     * Get workspace activity logs.
     */
    public function getWorkspaceActivities(int $workspaceId, array $filters = []): Collection
    {
        return $this->activityLogRepository->getByWorkspace($workspaceId, $filters);
    }

    /**
     * Get user activity logs.
     */
    public function getUserActivities(int $userId, array $filters = []): Collection
    {
        return $this->activityLogRepository->getByUser($userId, $filters);
    }

    /**
     * Get activities by action type.
     */
    public function getActivitiesByAction(string $action, array $filters = []): Collection
    {
        return $this->activityLogRepository->getByAction($action, $filters);
    }

    /**
     * Get activities for a specific subject.
     */
    public function getSubjectActivities(string $subjectType, int $subjectId): Collection
    {
        return $this->activityLogRepository->getBySubject($subjectType, $subjectId);
    }

    /**
     * Clean up old activity logs.
     */
    public function cleanupOldLogs(int $retentionDays = 90): int
    {
        return $this->activityLogRepository->deleteOlderThan($retentionDays);
    }

    /**
     * Get activity statistics.
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->activityLogRepository->getStatistics($filters);
    }

    /**
     * Bulk log activities.
     */
    public function bulkLogActivities(array $activities): Collection
    {
        $loggedActivities = collect();

        foreach ($activities as $activityData) {
            $loggedActivities->push($this->logActivity($activityData));
        }

        return $loggedActivities;
    }
}