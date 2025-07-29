<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubtaskRequest;
use App\Http\Requests\UpdateSubtaskRequest;
use App\Http\Resources\SubtaskResource;
use App\Models\Subtask;
use App\Models\Task;
use App\Services\SubtaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class SubtaskController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var SubtaskService
     */
    protected $subtaskService;

    /**
     * SubtaskController constructor.
     *
     * @param SubtaskService $subtaskService
     */
    public function __construct(SubtaskService $subtaskService)
    {
        $this->subtaskService = $subtaskService;
    }

    /**
     * Display a listing of the subtasks for a task.
     *
     * @param Task $task
     * @return AnonymousResourceCollection
     */
    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);
        
        $subtasks = $this->subtaskService->getSubtasksByTaskId($task->id);
        
        return SubtaskResource::collection($subtasks);
    }

    /**
     * Store a newly created subtask in storage.
     *
     * @param CreateSubtaskRequest $request
     * @return SubtaskResource
     */
    public function store(CreateSubtaskRequest $request): SubtaskResource
    {
        $task = Task::findOrFail($request->task_id);
        $this->authorize('update', $task);
        
        $subtask = $this->subtaskService->createSubtask($request->validated());
        
        return new SubtaskResource($subtask);
    }

    /**
     * Display the specified subtask.
     *
     * @param Subtask $subtask
     * @return SubtaskResource
     */
    public function show(Subtask $subtask): SubtaskResource
    {
        $this->authorize('view', $subtask->task);
        
        return new SubtaskResource($subtask);
    }

    /**
     * Update the specified subtask in storage.
     *
     * @param UpdateSubtaskRequest $request
     * @param Subtask $subtask
     * @return SubtaskResource
     */
    public function update(UpdateSubtaskRequest $request, Subtask $subtask): SubtaskResource
    {
        $this->authorize('update', $subtask->task);
        
        $subtask = $this->subtaskService->updateSubtask($subtask, $request->validated());
        
        return new SubtaskResource($subtask);
    }

    /**
     * Remove the specified subtask from storage.
     *
     * @param Subtask $subtask
     * @return JsonResponse
     */
    public function destroy(Subtask $subtask): JsonResponse
    {
        $this->authorize('update', $subtask->task);
        
        $this->subtaskService->deleteSubtask($subtask);
        
        return response()->json(['message' => 'Subtask deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Toggle the completion status of a subtask.
     *
     * @param Request $request
     * @param Subtask $subtask
     * @return SubtaskResource
     */
    public function toggleCompletion(Request $request, Subtask $subtask): SubtaskResource
    {
        $this->authorize('update', $subtask->task);
        
        $request->validate([
            'is_completed' => ['required', 'boolean'],
        ]);
        
        $subtask = $this->subtaskService->toggleCompletion($subtask, $request->is_completed);
        
        return new SubtaskResource($subtask);
    }

    /**
     * Reorder subtasks.
     *
     * @param Request $request
     * @param Task $task
     * @return JsonResponse
     */
    public function reorder(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        
        $request->validate([
            'subtask_ids' => ['required', 'array'],
            'subtask_ids.*' => ['exists:subtasks,id'],
        ]);
        
        $result = $this->subtaskService->reorderSubtasks($task, $request->subtask_ids);
        
        if ($result) {
            return response()->json(['message' => 'Subtasks reordered successfully'], Response::HTTP_OK);
        }
        
        return response()->json(['message' => 'Failed to reorder subtasks'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
