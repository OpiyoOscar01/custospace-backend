<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MilestoneController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var MilestoneService
     */
    protected $milestoneService;

    /**
     * MilestoneController constructor.
     *
     * @param MilestoneService $milestoneService
     */
    public function __construct(MilestoneService $milestoneService)
    {
        $this->milestoneService = $milestoneService;
    }

    /**
     * Display a listing of the milestones.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Milestone::class);
        
        $filters = $request->only([
            'project_id', 'is_completed', 'due_before', 'upcoming'
        ]);
        
        $relations = $request->input('with', []);
        $filters['with_relations'] = $relations;
        
        $perPage = $request->input('per_page', 15);
        
        $milestones = $this->milestoneService->getAllMilestones($filters, $perPage);
        
        return MilestoneResource::collection($milestones);
    }

    /**
     * Store a newly created milestone in storage.
     *
     * @param CreateMilestoneRequest $request
     * @return MilestoneResource
     */
    public function store(CreateMilestoneRequest $request): MilestoneResource
    {
        $project = Project::findOrFail($request->project_id);
        $this->authorize('update', $project);
        
        $milestone = $this->milestoneService->createMilestone($request->validated());
        
        // Load related entities if requested
        $relations = $request->input('with', []);
        if (!empty($relations)) {
            $milestone->load($relations);
        }
        
        return new MilestoneResource($milestone);
    }

    /**
     * Display the specified milestone.
     *
     * @param Request $request
     * @param Milestone $milestone
     * @return MilestoneResource
     */
    public function show(Request $request, Milestone $milestone): MilestoneResource
    {
        $this->authorize('view', $milestone);
        
        $relations = $request->input('with', []);
        if (!empty($relations)) {
            $milestone->load($relations);
        }
        
        return new MilestoneResource($milestone);
    }

    /**
     * Update the specified milestone in storage.
     *
     * @param UpdateMilestoneRequest $request
     * @param Milestone $milestone
     * @return MilestoneResource
     */
    public function update(UpdateMilestoneRequest $request, Milestone $milestone): MilestoneResource
    {
        $this->authorize('update', $milestone);
        
        $milestone = $this->milestoneService->updateMilestone($milestone, $request->validated());
        
        // Load related entities if requested
        $relations = $request->input('with', []);
        if (!empty($relations)) {
            $milestone->load($relations);
        }
        
        return new MilestoneResource($milestone);
    }

    /**
     * Remove the specified milestone from storage.
     *
     * @param Milestone $milestone
     * @return JsonResponse
     */
    public function destroy(Milestone $milestone): JsonResponse
    {
        $this->authorize('delete', $milestone);
        
        $this->milestoneService->deleteMilestone($milestone);
        
        return response()->json(['message' => 'Milestone deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Display a listing of the milestones for a specific project.
     *
     * @param Request $request
     * @param Project $project
     * @return AnonymousResourceCollection
     */
    public function byProject(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);
        
        $filters = $request->only([
            'is_completed', 'due_before', 'upcoming'
        ]);
        
        $relations = $request->input('with', []);
        $filters['with_relations'] = $relations;
        
        $perPage = $request->input('per_page', 15);
        
        $milestones = $this->milestoneService->getMilestonesByProject($project->id, $filters, $perPage);
        
        return MilestoneResource::collection($milestones);
    }

    /**
     * Toggle the completion status of a milestone.
     *
     * @param Request $request
     * @param Milestone $milestone
     * @return MilestoneResource
     */
    public function toggleCompletion(Request $request, Milestone $milestone): MilestoneResource
    {
        $this->authorize('update', $milestone);
        
        $request->validate([
            'is_completed' => ['required', 'boolean'],
        ]);
        
        $milestone = $this->milestoneService->toggleCompletion($milestone, $request->is_completed);
        
        return new MilestoneResource($milestone);
    }

    /**
     * Associate tasks with a milestone.
     *
     * @param Request $request
     * @param Milestone $milestone
     * @return MilestoneResource
     */
    public function syncTasks(Request $request, Milestone $milestone): MilestoneResource
    {
        $this->authorize('update', $milestone);
        
        $request->validate([
            'task_ids' => ['required', 'array'],
            'task_ids.*' => ['exists:tasks,id'],
        ]);
        
        $milestone = $this->milestoneService->syncTasks($milestone, $request->task_ids);
        
        return new MilestoneResource($milestone->load('tasks'));
    }

    /**
     * Reorder milestones within a project.
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        
        $request->validate([
            'milestone_ids' => ['required', 'array'],
            'milestone_ids.*' => ['exists:milestones,id'],
        ]);
        
        $result = $this->milestoneService->reorderMilestones($project->id, $request->milestone_ids);
        
        if ($result) {
            return response()->json(['message' => 'Milestones reordered successfully'], Response::HTTP_OK);
        }
        
        return response()->json(['message' => 'Failed to reorder milestones'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
