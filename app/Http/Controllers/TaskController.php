<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var TaskService
     */
    protected $taskService;

    /**
     * TaskController constructor.
     *
     * @param TaskService $taskService
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
        $this->authorizeResource(Task::class, 'task', [
            'except' => ['index', 'changeStatus', 'assignTask', 'addDependency', 'removeDependency', 'attachMilestone', 'detachMilestone']
        ]);
    }

    /**
     * Display a listing of the tasks.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);
        
        $filters = $request->only([
            'workspace_id', 'project_id', 'status_id', 'assignee_id',
            'reporter_id', 'priority', 'type', 'due_before'
        ]);
        
        $relations = $request->input('with', []);
        $filters['with_relations'] = $relations;
        
        $perPage = $request->input('per_page', 15);
        
        $tasks = $this->taskService->getAllTasks($filters, $perPage);
        
        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created task in storage.
     *
     * @param CreateTaskRequest $request
     * @return TaskResource
     */
    public function store(CreateTaskRequest $request): TaskResource
    {
        $task = $this->taskService->createTask($request->validated());
        
        // Load related entities if requested
        $relations = $request->input('with', []);
        if (!empty($relations)) {
            $task->load($relations);
        }
        
        return new TaskResource($task);
    }

    /**
     * Display the specified task.
     *
     * @param Request $request
     * @param Task $task
     * @return TaskResource
     */
    public function show(Request $request, Task $task): TaskResource
    {
        $relations = $request->input('with', []);
        if (!empty($relations)) {
            $task->load($relations);
        }
        
        return new TaskResource($task);
    }

    /**
     * Update the specified task in storage.
     *
     * @param UpdateTaskRequest $request
     * @param Task $task
     * @return TaskResource
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task = $this->taskService->updateTask($task, $request->validated());
        
        // Load related entities if requested
        $relations = $request->input('with', []);
        if (!empty($relations)) {
            $task->load($relations);
        }
        
        return new TaskResource($task);
    }

    /**
     * Remove the specified task from storage.
     *
     * @param Task $task
     * @return JsonResponse
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task);
        
        return response()->json(['message' => 'Task deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Change the status of a task.
     *
     * @param Request $request
     * @param Task $task
     * @return TaskResource
     */
    public function changeStatus(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        
        $request->validate([
            'status_id' => ['required', 'exists:statuses,id'],
        ]);
        
        $task = $this->taskService->changeStatus($task, $request->status_id);
        
        return new TaskResource($task);
    }

    /**
     * Assign a task to a user.
     *
     * @param Request $request
     * @param Task $task
     * @return TaskResource
     */
    public function assignTask(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        
        $request->validate([
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);
        
        $task = $this->taskService->assignTask($task, $request->assignee_id);
        
        return new TaskResource($task);
    }

    /**
     * Add a dependency to a task.
     *
     * @param Request $request
     * @param Task $task
     * @return TaskResource
     */
    public function addDependency(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        
        $request->validate([
            'dependency_ids' => ['required', 'array'],
            'dependency_ids.*' => ['exists:tasks,id'],
            'dependency_types' => ['required', 'array'],
            'dependency_types.*' => ['in:blocks,relates_to,duplicates'],
        ]);
        
        $task = $this->taskService->addDependencies(
            $task, 
            $request->dependency_ids,
            $request->dependency_types
        );
        
        return new TaskResource($task->load('dependencies'));
    }

    /**
     * Remove a dependency from a task.
     *
     * @param Request $request
     * @param Task $task
     * @return TaskResource
     */
    public function removeDependency(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        
        $request->validate([
            'dependency_ids' => ['required', 'array'],
            'dependency_ids.*' => ['exists:tasks,id'],
        ]);
        
        $task = $this->taskService->removeDependencies($task, $request->dependency_ids);
        
        return new TaskResource($task->load('dependencies'));
    }
    
    /**
     * Attach a milestone to a task.
     *
     * @param Request $request
     * @param Task $task
     * @return TaskResource
     */
    public function syncMilestones(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        
        $request->validate([
            'milestone_ids' => ['required', 'array'],
            'milestone_ids.*' => ['exists:milestones,id'],
        ]);
        
        $task = $this->taskService->syncMilestones($task, $request->milestone_ids);
        
        return new TaskResource($task->load('milestones'));
    }
}
