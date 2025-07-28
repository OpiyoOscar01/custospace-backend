<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WorkspaceController extends Controller
{
    use AuthorizesRequests;

    protected $workspaceService;

    /**
     * Create a new controller instance.
     */
    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
        $this->authorizeResource(Workspace::class, 'workspace');
    }

    /**
     * Display a listing of the workspaces.
     */
    public function index(Request $request)
    {
        $workspaces = $this->workspaceService->getAllWorkspaces($request->all());
        return WorkspaceResource::collection($workspaces);
    }

    /**
     * Store a newly created workspace in storage.
     */
    public function store(CreateWorkspaceRequest $request)
    {
        $workspace = $this->workspaceService->createWorkspace($request->validated());
        return new WorkspaceResource($workspace);
    }

    /**
     * Display the specified workspace.
     */
    public function show(Workspace $workspace)
    {
        $workspace = $this->workspaceService->getWorkspaceById($workspace->id);
        return new WorkspaceResource($workspace);
    }

    /**
     * Update the specified workspace in storage.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace)
    {
        $workspace = $this->workspaceService->updateWorkspace($workspace, $request->validated());
        return new WorkspaceResource($workspace);
    }

    /**
     * Remove the specified workspace from storage.
     */
    public function destroy(Workspace $workspace)
    {
        $this->workspaceService->deleteWorkspace($workspace);
        return response()->noContent();
    }

    /**
     * Activate a workspace.
     * 
     * @param Workspace $workspace
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Workspace $workspace)
    {
        $this->authorize('update', $workspace);
        $workspace = $this->workspaceService->activateWorkspace($workspace);
        return new WorkspaceResource($workspace);
    }

    /**
     * Deactivate a workspace.
     * 
     * @param Workspace $workspace
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate(Workspace $workspace)
    {
        $this->authorize('update', $workspace);
        $workspace = $this->workspaceService->deactivateWorkspace($workspace);
        return new WorkspaceResource($workspace);
    }

    /**
     * Assign a user to the workspace.
     * 
     * @param Request $request
     * @param Workspace $workspace
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignUser(Request $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member,viewer',
        ]);

        $workspace = $this->workspaceService->assignUser(
            $workspace, 
            $validated['user_id'], 
            $validated['role']
        );

        return new WorkspaceResource($workspace);
    }
}
