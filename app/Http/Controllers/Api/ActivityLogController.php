<?php
// app/Http/Controllers/Api/ActivityLogController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateActivityLogRequest;
use App\Http\Requests\UpdateActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Activity Log API Controller
 * 
 * Handles HTTP requests for activity log operations
 */
class ActivityLogController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
        // Apply authorization policies
        $this->authorizeResource(ActivityLog::class, 'activity_log');
    }

    /**
     * Display a listing of activity logs.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'workspace_id',
            'user_id',
            'action',
            'subject_type',
            'date_from',
            'date_to',
            'search'
        ]);

        $perPage = min($request->get('per_page', 15), 100);
        $activityLogs = $this->activityLogService->getPaginatedLogs($filters, $perPage);

        return ActivityLogResource::collection($activityLogs);
    }

    /**
     * Store a newly created activity log.
     * 
     * @param CreateActivityLogRequest $request
     * @return ActivityLogResource
     */
    public function store(CreateActivityLogRequest $request): ActivityLogResource
    {
        $activityLog = $this->activityLogService->logActivity($request->validated());

        return new ActivityLogResource($activityLog);
    }

    /**
     * Display the specified activity log.
     * 
     * @param ActivityLog $activityLog
     * @return ActivityLogResource
     */
    public function show(ActivityLog $activityLog): ActivityLogResource
    {
        return new ActivityLogResource($activityLog->load(['user', 'workspace', 'subject']));
    }

    /**
     * Update the specified activity log.
     * 
     * @param UpdateActivityLogRequest $request
     * @param ActivityLog $activityLog
     * @return ActivityLogResource
     */
    public function update(UpdateActivityLogRequest $request, ActivityLog $activityLog): ActivityLogResource
    {
        $activityLog->update($request->validated());

        return new ActivityLogResource($activityLog->fresh(['user', 'workspace', 'subject']));
    }

    /**
     * Remove the specified activity log.
     * 
     * @param ActivityLog $activityLog
     * @return JsonResponse
     */
    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->delete();

        return response()->json([
            'message' => 'Activity log deleted successfully.'
        ]);
    }

    /**
     * Get workspace activity logs.
     * 
     * @param Request $request
     * @param int $workspaceId
     * @return AnonymousResourceCollection
     */
    public function getWorkspaceActivities(Request $request, int $workspaceId): AnonymousResourceCollection
    {
        $filters = $request->only(['limit', 'action', 'user_id']);
        $activities = $this->activityLogService->getWorkspaceActivities($workspaceId, $filters);

        return ActivityLogResource::collection($activities);
    }

    /**
     * Get activity statistics.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $filters = $request->only(['workspace_id', 'date_from', 'date_to']);
        $statistics = $this->activityLogService->getStatistics($filters);

        return response()->json([
            'data' => $statistics
        ]);
    }

    /**
     * Clean up old activity logs.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cleanup(Request $request): JsonResponse
    {
        $this->authorize('cleanup', ActivityLog::class);

        $retentionDays = $request->get('retention_days', 90);
        $deletedCount = $this->activityLogService->cleanupOldLogs($retentionDays);

        return response()->json([
            'message' => "Cleaned up {$deletedCount} old activity logs.",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Bulk create activity logs.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function bulkStore(Request $request): AnonymousResourceCollection
    {
        $this->authorize('create', ActivityLog::class);

        $request->validate([
            'activities' => ['required', 'array', 'max:100'],
            'activities.*.workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'activities.*.action' => ['required', 'string', 'max:255'],
            'activities.*.description' => ['required', 'string', 'max:1000'],
            'activities.*.subject_type' => ['required', 'string', 'max:255'],
            'activities.*.subject_id' => ['required', 'integer'],
            'activities.*.properties' => ['nullable', 'array'],
        ]);

        $activities = $this->activityLogService->bulkLogActivities($request->activities);

        return ActivityLogResource::collection($activities);
    }
}