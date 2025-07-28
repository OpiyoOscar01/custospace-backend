<?php
// app/Policies/StatusPolicy.php

namespace App\Policies;

use App\Models\Status;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Status Policy
 * 
 * Handles authorization logic for status operations.
 */
class StatusPolicy
{
    /**
     * Determine whether the user can view any statuses.
     */
    public function viewAny(User $user): bool
    {
        // User can view statuses if they have access to any workspace
        return $user->workspaces()->exists();
    }

    /**
     * Determine whether the user can view the status.
     */
    public function view(User $user, Status $status): bool
    {
        // User can view status if they have access to the workspace
        return $this->hasWorkspaceAccess($user, $status->workspace_id);
    }

    /**
     * Determine whether the user can create statuses.
     */
    public function create(User $user): bool
    {
        // User can create statuses if they are an admin in at least one workspace
        return $user->workspaces()->wherePivot('role', 'admin')->exists() ||
               $user->workspaces()->wherePivot('role', 'owner')->exists();
    }

    /**
     * Determine whether the user can update the status.
     */
    public function update(User $user, Status $status): bool
    {
        // User can update status if they are a workspace admin/owner
        return $this->isWorkspaceAdmin($user, $status->workspace_id);
    }

    /**
     * Determine whether the user can delete the status.
     */
    public function delete(User $user, Status $status): bool
    {
        // User can delete status if they are a workspace admin/owner
        // Default statuses cannot be deleted
        return !$status->is_default && $this->isWorkspaceAdmin($user, $status->workspace_id);
    }

    /**
     * Determine whether the user has access to the workspace.
     */
    private function hasWorkspaceAccess(User $user, int $workspaceId): bool
    {
        return $user->workspaces()->where('workspace_id', $workspaceId)->exists();
    }

    /**
     * Determine whether the user is a workspace admin or owner.
     */
    private function isWorkspaceAdmin(User $user, int $workspaceId): bool
    {
        return $user->workspaces()
                    ->where('workspace_id', $workspaceId)
                    ->whereIn('role', ['admin', 'owner'])
                    ->exists();
    }
}
