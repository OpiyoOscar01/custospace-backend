<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\PipelineResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\PipelineService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Project API Controller
 * 
 * Handles HTTP requests for project management operations.
 * Provides RESTful endpoints and custom actions for projects.
 */
class ProjectController extends Controller
{
     protected ProjectService $projectService;
     protected PipelineService $pipelineService;

    public function __construct(ProjectService $projectService, PipelineService $pipelineService)
    {
        $this->projectService = $projectService;
        $this->pipelineService = $pipelineService;
    }

    /**
     * Display a listing of projects.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Project::class);

        // Extract filters from request
        $filters = $request->only([
            'workspace_id', 'status', 'priority', 'team_id', 
            'owner_id', 'search', 'is_template', 'sort_by', 'sort_direction'
        ]);

        $perPage = $request->get('per_page', 15);
        $projects = $this->projectService->getAllProjects($filters, $perPage);

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     * 
     * @param CreateProjectRequest $request
     * @return JsonResponse
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $project = $this->projectService->createProject($request->validated());

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => new ProjectResource($project)
        ], 201);
    }

    /**
     * Display the specified project.
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json([
            'data' => new ProjectResource($project->load(['workspace', 'team', 'owner', 'users', 'pipelines']))
        ]);
    }

    /**
     * Update the specified project.
     * 
     * @param UpdateProjectRequest $request
     * @param Project $project
     * @return JsonResponse
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $updatedProject = $this->projectService->updateProject($project, $request->validated());

        return response()->json([
            'message' => 'Project updated successfully.',
            'data' => new ProjectResource($updatedProject)
        ]);
    }

    /**
     * Remove the specified project.
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projectService->deleteProject($project);

        return response()->json([
            'message' => 'Project deleted successfully.'
        ]);
    }

    /**
     * Activate a project.
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function activate(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $activatedProject = $this->projectService->activateProject($project);

        return response()->json([
            'message' => 'Project activated successfully.',
            'data' => new ProjectResource($activatedProject)
        ]);
    }

    /**
     * Deactivate a project (set to on_hold).
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function deactivate(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $deactivatedProject = $this->projectService->deactivateProject($project);

        return response()->json([
            'message' => 'Project deactivated successfully.',
            'data' => new ProjectResource($deactivatedProject)
        ]);
    }

    /**
     * Complete a project.
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function complete(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $completedProject = $this->projectService->completeProject($project);

        return response()->json([
            'message' => 'Project completed successfully.',
            'data' => new ProjectResource($completedProject)
        ]);
    }

    /**
     * Cancel a project.
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function cancel(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $cancelledProject = $this->projectService->cancelProject($project);

        return response()->json([
            'message' => 'Project cancelled successfully.',
            'data' => new ProjectResource($cancelledProject)
        ]);
    }

    /**
     * Assign user to project.
     * 
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function assignUser(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:' . implode(',', array_keys(Project::USER_ROLES))
        ]);

        $this->projectService->assignUserToProject(
            $project, 
            $request->user_id, 
            $request->role
        );

        return response()->json([
            'message' => 'User assigned to project successfully.',
            'data' => new ProjectResource($project->load(['users']))
        ]);
    }

    /**
     * Remove user from project.
     * 
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function removeUser(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $this->projectService->removeUserFromProject($project, $request->user_id);

        return response()->json([
            'message' => 'User removed from project successfully.',
            'data' => new ProjectResource($project->load(['users']))
        ]);
    }

    /**
     * Update user role in project.
     * 
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function updateUserRole(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:' . implode(',', array_keys(Project::USER_ROLES))
        ]);

        $this->projectService->updateUserRole(
            $project, 
            $request->user_id, 
            $request->role
        );

        return response()->json([
            'message' => 'User role updated successfully.',
            'data' => new ProjectResource($project->load(['users']))
        ]);
    }

    /**
     * Update project progress.
     * 
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function updateProgress(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);

        $updatedProject = $this->projectService->updateProgress($project, $request->progress);

        return response()->json([
            'message' => 'Project progress updated successfully.',
            'data' => new ProjectResource($updatedProject)
        ]);
    }

    /**
     * Get project statistics.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Project::class);

        $workspaceId = $request->get('workspace_id');
        $statistics = $this->projectService->getProjectStatistics($workspaceId);

        return response()->json([
            'data' => $statistics
        ]);
    }

    /**
     * Get the default pipeline for this project or create one if not exists.
     * 
     * @param Project $project
     * @return JsonResponse
     */
    public function getDefaultPipeline(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);
        
        // First try to get project-specific default pipeline
        $pipeline = $this->pipelineService->getDefaultForProject($project->id);
        
        // If not found, get workspace default pipeline
        if (!$pipeline) {
            $pipeline = $this->pipelineService->getOrCreateDefaultPipelineForWorkspace($project->workspace_id);
        }
        
        return response()->json([
            'data' => new PipelineResource($pipeline)
        ]);
    }

        /**
         * Create a new pipeline for this project.
         * 
         * @param Request $request
         * @param Project $project
         * @return JsonResponse
         */
        public function createPipeline(Request $request, Project $project): JsonResponse
        {
            Gate::authorize('update', $project);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
                'description' => 'nullable|string',
                'is_default' => 'nullable|boolean',
                'statuses' => 'nullable|array',
                'statuses.*' => 'integer|exists:statuses,id'
            ]);
            
            $pipelineData = [
                'workspace_id' => $project->workspace_id,
                'project_id' => $project->id,
                'name' => $request->name,
                'slug' => $request->slug ?? Str::slug($request->name),
                'description' => $request->description,
                'is_default' => $request->is_default ?? false,
            ];
            
            $statusIds = $request->statuses ?? [];
            $pipeline = $this->pipelineService->createPipeline($pipelineData, $statusIds);
            
            return response()->json([
                'message' => 'Project pipeline created successfully.',
                'data' => new PipelineResource($pipeline)
            ], 201);
        }

        /**
         * Update project status.
         * 
         * @param Request $request
         * @param Project $project
         * @return JsonResponse
         */
        public function updateStatus(Request $request, Project $project): JsonResponse
        {
            Gate::authorize('update', $project);
            
            $request->validate([
                'status' => [
                    'required',
                    Rule::in(array_keys(Project::STATUSES))
                ]
            ]);
            
            $updatedProject = $this->projectService->updateProject($project, [
                'status' => $request->status
            ]);
            
            return response()->json([
                'message' => 'Project status updated successfully.',
                'data' => new ProjectResource($updatedProject)
            ]);
        }
 

}
