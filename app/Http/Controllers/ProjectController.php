<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Project API Controller
 * 
 * Handles HTTP requests for project management operations.
 * Provides RESTful endpoints and custom actions for projects.
 */
class ProjectController extends Controller
{
     protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
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
}
