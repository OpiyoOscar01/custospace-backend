<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Activity Log Repository Implementation
 * 
 * Handles all database operations for activity logs
 */
class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    /**
     * Get paginated activity logs with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ActivityLog::with(['user', 'workspace', 'subject']);

        // Apply filters
        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('action', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new activity log entry.
     */
    public function create(array $data): ActivityLog
    {
        return ActivityLog::create($data);
    }

    /**
     * Find activity log by ID.
     */
    public function findById(int $id): ?ActivityLog
    {
        return ActivityLog::with(['user', 'workspace', 'subject'])->find($id);
    }

    /**
     * Get activity logs by workspace.
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection
    {
        $query = ActivityLog::where('workspace_id', $workspaceId)->with(['user', 'subject']);

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get activity logs by user.
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = ActivityLog::where('user_id', $userId)->with(['workspace', 'subject']);

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get activity logs by action type.
     */
    public function getByAction(string $action, array $filters = []): Collection
    {
        $query = ActivityLog::byAction($action)->with(['user', 'workspace', 'subject']);

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get activity logs for a specific subject.
     */
    public function getBySubject(string $subjectType, int $subjectId): Collection
    {
        return ActivityLog::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with(['user', 'workspace'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete activity logs older than specified days.
     */
    public function deleteOlderThan(int $days): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get activity statistics.
     */
    public function getStatistics(array $filters = []): array
    {
        $query = ActivityLog::query();

        // Apply workspace filter if provided
        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        // Apply date range filter if provided
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_activities' => $query->count(),
            'activities_by_action' => $query->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'activities_by_day' => $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }
}