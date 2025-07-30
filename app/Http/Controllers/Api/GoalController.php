<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Models\User;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Goal API Controller
 * 
 * Handles all API endpoints for goal management
 */
class GoalController extends Controller
{
    use AuthorizesRequests;
    /**
     * Goal service instance
     *
     * @var GoalService
     */
    protected GoalService $goalService;

    /**
     * Constructor
     *
     * @param GoalService $goalService
     */
    public function __construct(GoalService $goalService)
    {
        $this->goalService = $goalService;
    
        $this->authorizeResource(Goal::class, 'goal');
    }

    /**
     * Display a listing of goals
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'workspace_id',
            'team_id',
            'status',
            'owner_id',
            'search',
            'sort_by',
            'sort_direction',
            'per_page'
        ]);

        $goals = $this->goalService->getPaginatedGoals($filters);

        return GoalResource::collection($goals);
    }

    /**
     * Store a newly created goal in storage
     *
     * @param CreateGoalRequest $request
     * @return GoalResource|JsonResponse
     */
    public function store(CreateGoalRequest $request): GoalResource|JsonResponse
    {
        try {
            $goal = $this->goalService->createGoal($request->validated());

            return new GoalResource($goal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified goal
     *
     * @param Goal $goal
     * @return GoalResource
     */
    public function show(Goal $goal): GoalResource
    {
        // Load relationships
        $goal->load(['workspace', 'team', 'owner', 'tasks']);
        
        return new GoalResource($goal);
    }

    /**
     * Update the specified goal in storage
     *
     * @param UpdateGoalRequest $request
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function update(UpdateGoalRequest $request, Goal $goal): GoalResource|JsonResponse
    {
        try {
            $updatedGoal = $this->goalService->updateGoal($goal, $request->validated());

            return new GoalResource($updatedGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified goal from storage
     *
     * @param Goal $goal
     * @return JsonResponse
     */
    public function destroy(Goal $goal): JsonResponse
    {
        try {
            $this->goalService->deleteGoal($goal);

            return response()->json([
                'message' => 'Goal deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Activate the specified goal
     *
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function activate(Goal $goal): GoalResource|JsonResponse
    {
        try {
            $this->authorize('update', $goal);
            
            $activatedGoal = $this->goalService->activateGoal($goal);

            return new GoalResource($activatedGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to activate goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Complete the specified goal
     *
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function complete(Goal $goal): GoalResource|JsonResponse
    {
        try {
            $this->authorize('update', $goal);
            
            $completedGoal = $this->goalService->completeGoal($goal);

            return new GoalResource($completedGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to complete goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel the specified goal
     *
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function cancel(Goal $goal): GoalResource|JsonResponse
    {
        try {
            $this->authorize('update', $goal);
            
            $cancelledGoal = $this->goalService->cancelGoal($goal);

            return new GoalResource($cancelledGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update goal progress
     *
     * @param Request $request
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function updateProgress(Request $request, Goal $goal): GoalResource|JsonResponse
    {
        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);

        try {
            $this->authorize('update', $goal);
            
            $updatedGoal = $this->goalService->updateProgress($goal, $request->input('progress'));

            return new GoalResource($updatedGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update goal progress',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Assign user to goal
     *
     * @param Request $request
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function assignUser(Request $request, Goal $goal): GoalResource|JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        try {
            $this->authorize('update', $goal);
            
            $user = User::findOrFail($request->input('user_id'));
            $updatedGoal = $this->goalService->assignUser($goal, $user);

            return new GoalResource($updatedGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to assign user to goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Assign tasks to goal
     *
     * @param Request $request
     * @param Goal $goal
     * @return GoalResource|JsonResponse
     */
    public function assignTasks(Request $request, Goal $goal): GoalResource|JsonResponse
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer|exists:tasks,id'
        ]);

        try {
            $this->authorize('update', $goal);
            
            $updatedGoal = $this->goalService->assignTasks($goal, $request->input('task_ids'));

            return new GoalResource($updatedGoal);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to assign tasks to goal',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}