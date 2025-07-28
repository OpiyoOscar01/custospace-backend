<?php

namespace App\Repositories\Contracts;

use App\Models\Workspace;

interface WorkspaceRepositoryInterface
{
    /**
     * Get all workspaces with pagination.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllWorkspaces(array $filters = []);

    /**
     * Get workspace by ID with relationships.
     *
     * @param int $id
     * @return Workspace
     */
    public function getWorkspaceById(int $id);

    /**
     * Create a new workspace.
     *
     * @param array $data
     * @return Workspace
     */
    public function createWorkspace(array $data);

    /**
     * Update an existing workspace.
     *
     * @param Workspace $workspace
     * @param array $data
     * @return Workspace
     */
    public function updateWorkspace(Workspace $workspace, array $data);

    /**
     * Delete a workspace.
     *
     * @param Workspace $workspace
     * @return bool
     */
    public function deleteWorkspace(Workspace $workspace);
}
