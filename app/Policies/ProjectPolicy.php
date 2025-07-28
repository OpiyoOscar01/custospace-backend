<?php
// app/Policies/ProjectPolicy.php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Project Policy
 * 
 * Handles authorization logic for project operations.
 */
class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        // User can view projects if they have access to any workspace
        return $user->workspaces()->exists();
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        // User can view if they are:
        // 1. Project owner
        // 2. Project team member
        // 3. Project user (assigned to project)
        // 4. Workspace admin/owner
        return $this->canAccessProject($user, $project);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        // User can create projects if they have access to at least one workspace
        // where they can create projects (workspace member with appropriate permissions)
        return $user->workspaces()->wherePivot('role', '!=', 'viewer')->exists();
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        // User can update if they are:
        // 1. Project owner
        // 2. Project manager
        // 3. Workspace admin/owner
        return $this->canManageProject($user, $project);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        // User can delete if they are:
        // 1. Project owner
        // 2. Workspace admin/owner
        return $user->id === $project->owner_id ||
               $this->isWorkspaceAdmin($user, $project->workspace_id);
    }

    /**
     * Determine whether the user can restore the project.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the project.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Check if user can access the project.
     */
    private function canAccessProject(User $user, Project $project): bool
    {
        // Check if user is project owner
        if ($user->id === $project->owner_id) {
            return true;
        }

        // Check if user is assigned to project
        if ($project->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check if user is workspace admin/owner
        if ($this->isWorkspaceAdmin($user, $project->workspace_id)) {
            return true;
        }

        // Check if user is team member (if project has team)
        if ($project->team_id && $project->team->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can manage the project.
     */
    private function canManageProject(User $user, Project $project): bool
    {
        // Check if user is project owner
        if ($user->id === $project->owner_id) {
            return true;
        }

        // Check if user has manager role in project
        if ($project->users()->where('user_id', $user->id)->wherePivot('role', 'manager')->exists()) {
            return true;
        }

        // Check if user is workspace admin/owner
        if ($this->isWorkspaceAdmin($user, $project->workspace_id)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is workspace admin or owner.
     */
    private function isWorkspaceAdmin(User $user, int $workspaceId): bool
    {
        return $user->workspaces()
                   ->where('workspace_id', $workspaceId)
                   ->whereIn('role', ['admin', 'owner'])
                   ->exists();
    }
}
