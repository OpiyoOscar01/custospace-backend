<?php

namespace App\Services;

use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkspaceService
{
    protected $workspaceRepository;

    /**
     * Create a new service instance.
     */
    public function __construct(WorkspaceRepositoryInterface $workspaceRepository)
    {
        $this->workspaceRepository = $workspaceRepository;
    }

    /**
     * Get all workspaces with optional filtering.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllWorkspaces(array $filters = [])
    {
        return $this->workspaceRepository->getAllWorkspaces($filters);
    }

    /**
     * Get workspace by ID with relationships.
     *
     * @param int $id
     * @return Workspace
     */
    public function getWorkspaceById(int $id)
    {
        return $this->workspaceRepository->getWorkspaceById($id);
    }

    /**
     * Create a new workspace.
     *
     * @param array $data
     * @return Workspace
     */
    public function createWorkspace(array $data)
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $workspace = $this->workspaceRepository->createWorkspace($data);

        // Assign current user as owner
        if (Auth::check()) {
            $workspace->users()->attach(Auth::id(), ['role' => 'owner']);
        }

        return $workspace;
    }

    /**
     * Update an existing workspace.
     *
     * @param Workspace $workspace
     * @param array $data
     * @return Workspace
     */
    public function updateWorkspace(Workspace $workspace, array $data)
    {
        return $this->workspaceRepository->updateWorkspace($workspace, $data);
    }

    /**
     * Delete a workspace.
     *
     * @param Workspace $workspace
     * @return bool
     */
    public function deleteWorkspace(Workspace $workspace)
    {
        return $this->workspaceRepository->deleteWorkspace($workspace);
    }

    /**
     * Activate a workspace.
     *
     * @param Workspace $workspace
     * @return Workspace
     */
    public function activateWorkspace(Workspace $workspace)
    {
        return $this->workspaceRepository->updateWorkspace($workspace, ['is_active' => true]);
    }

    /**
     * Deactivate a workspace.
     *
     * @param Workspace $workspace
     * @return Workspace
     */
    public function deactivateWorkspace(Workspace $workspace)
    {
        return $this->workspaceRepository->updateWorkspace($workspace, ['is_active' => false]);
    }

    /**
     * Assign a user to the workspace.
     *
     * @param Workspace $workspace
     * @param int $userId
     * @param string $role
     * @return Workspace
     */
    public function assignUser(Workspace $workspace, int $userId, string $role)
    {
        $workspace->users()->syncWithoutDetaching([
            $userId => [
                'role' => $role,
                'joined_at' => now(),
            ]
        ]);

        return $workspace->fresh(['users']);
    }
}
