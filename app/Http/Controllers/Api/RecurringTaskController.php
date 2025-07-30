<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRecurringTaskRequest;
use App\Http\Requests\UpdateRecurringTaskRequest;
use App\Http\Resources\RecurringTaskResource;
use App\Models\RecurringTask;
use App\Services\RecurringTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class RecurringTaskController
 * 
 * Handles API endpoints for recurring task management
 */
class RecurringTaskController extends Controller
{
    use AuthorizesRequests;
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected RecurringTaskService $recurringTaskService
    ) {
        $this->authorizeResource(RecurringTask::class, 'recurring_task');
    }

    /**
     * Display a listing of recurring tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $recurringTasks = $this->recurringTaskService->getAllRecurringTasks($request->all());
        
        return RecurringTaskResource::collection($recurringTasks);
    }

    /**
     * Store a newly created recurring task.
     */
    public function store(CreateRecurringTaskRequest $request): RecurringTaskResource
    {
        $recurringTask = $this->recurringTaskService->createRecurringTask($request->validated());
        
        return new RecurringTaskResource($recurringTask);
    }

    /**
     * Display the specified recurring task.
     */
    public function show(RecurringTask $recurringTask): RecurringTaskResource
    {
        return new RecurringTaskResource($recurringTask->load(['task']));
    }

    /**
     * Update the specified recurring task.
     */
    public function update(UpdateRecurringTaskRequest $request, RecurringTask $recurringTask): RecurringTaskResource
    {
        $updatedRecurringTask = $this->recurringTaskService->updateRecurringTask($recurringTask, $request->validated());
        
        return new RecurringTaskResource($updatedRecurringTask);
    }

    /**
     * Remove the specified recurring task.
     */
    public function destroy(RecurringTask $recurringTask): JsonResponse
    {
        $this->recurringTaskService->deleteRecurringTask($recurringTask);
        
        return response()->json([
            'message' => 'Recurring task deleted successfully.'
        ]);
    }

    /**
     * Activate a recurring task.
     */
    public function activate(RecurringTask $recurringTask): RecurringTaskResource
    {
        $this->authorize('update', $recurringTask);
        
        $activatedTask = $this->recurringTaskService->activateRecurringTask($recurringTask);
        
        return new RecurringTaskResource($activatedTask);
    }

    /**
     * Deactivate a recurring task.
     */
    public function deactivate(RecurringTask $recurringTask): RecurringTaskResource
    {
        $this->authorize('update', $recurringTask);
        
        $deactivatedTask = $this->recurringTaskService->deactivateRecurringTask($recurringTask);
        
        return new RecurringTaskResource($deactivatedTask);
    }

    /**
     * Get due recurring tasks.
     */
    public function due(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RecurringTask::class);
        
        $dueRecurringTasks = $this->recurringTaskService->getDueRecurringTasks();
        
        return RecurringTaskResource::collection($dueRecurringTasks);
    }

    /**
     * Process due recurring tasks (create new task instances).
     */
    public function processDue(): JsonResponse
    {
        $this->authorize('create', RecurringTask::class);
        
        $processedCount = $this->recurringTaskService->processDueRecurringTasks();
        
        return response()->json([
            'message' => "Processed {$processedCount} due recurring tasks.",
            'processed_count' => $processedCount
        ]);
    }

    /**
     * Update next due date for a recurring task.
     */
    public function updateNextDueDate(RecurringTask $recurringTask): RecurringTaskResource
    {
        $this->authorize('update', $recurringTask);
        
        $updatedTask = $this->recurringTaskService->updateNextDueDate($recurringTask);
        
        return new RecurringTaskResource($updatedTask);
    }
}
