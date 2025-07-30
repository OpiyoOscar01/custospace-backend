<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTimeLogRequest;
use App\Http\Requests\UpdateTimeLogRequest;
use App\Http\Resources\TimeLogResource;
use App\Models\TimeLog;
use App\Services\TimeLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class TimeLogController
 * 
 * Handles API endpoints for time log management
 */
class TimeLogController extends Controller
{
    use AuthorizesRequests;
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TimeLogService $timeLogService
    ) {
        $this->authorizeResource(TimeLog::class, 'time_log');
    }

    /**
     * Display a listing of time logs.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $timeLogs = $this->timeLogService->getAllTimeLogs($request->all());
        
        return TimeLogResource::collection($timeLogs);
    }

    /**
     * Store a newly created time log.
     */
    public function store(CreateTimeLogRequest $request): TimeLogResource
    {
        $timeLog = $this->timeLogService->createTimeLog($request->validated());
        
        return new TimeLogResource($timeLog);
    }

    /**
     * Display the specified time log.
     */
    public function show(TimeLog $timeLog): TimeLogResource
    {
        return new TimeLogResource($timeLog->load(['user', 'task']));
    }

    /**
     * Update the specified time log.
     */
    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog): TimeLogResource
    {
        $updatedTimeLog = $this->timeLogService->updateTimeLog($timeLog, $request->validated());
        
        return new TimeLogResource($updatedTimeLog);
    }

    /**
     * Remove the specified time log.
     */
    public function destroy(TimeLog $timeLog): JsonResponse
    {
        $this->timeLogService->deleteTimeLog($timeLog);
        
        return response()->json([
            'message' => 'Time log deleted successfully.'
        ]);
    }

    /**
     * Stop a running time log.
     */
    public function stop(Request $request, TimeLog $timeLog): TimeLogResource
    {
        $this->authorize('update', $timeLog);
        
        if (!$timeLog->isRunning()) {
            // Optionally, you can add a custom attribute or message to the resource
            $resource = new TimeLogResource($timeLog);
            $resource->additional(['message' => 'Time log is already stopped.']);
            return $resource;
        }

        $stoppedTimeLog = $this->timeLogService->stopTimeLog($timeLog, $request->input('ended_at'));
        
        return new TimeLogResource($stoppedTimeLog);
    }

    /**
     * Start a new time log for a user.
     */
    public function start(Request $request): TimeLogResource
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'task_id' => 'required|exists:tasks,id',
            'description' => 'nullable|string|max:1000',
            'is_billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
        ]);

        $this->authorize('create', TimeLog::class);

        $timeLog = $this->timeLogService->startTimeLog($request->validated());
        
        return new TimeLogResource($timeLog);
    }

    /**
     * Get time logs summary/statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'task_id' => 'nullable|exists:tasks,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $summary = $this->timeLogService->getTimeLogsSummary($request->all());
        
        return response()->json($summary);
    }

    /**
     * Get billable time logs for invoicing.
     */
    public function billable(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $billableTimeLogs = $this->timeLogService->getBillableTimeLogs($request->all());
        
        return TimeLogResource::collection($billableTimeLogs);
    }
}
