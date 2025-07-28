<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view any workspaces.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view workspaces
    }

    /**
     * Determine whether the user can view the workspace.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        // Users can view workspaces they belong to
        return $workspace->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create workspaces.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create workspaces
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        // Only owners and admins can update workspaces
        $pivot = $workspace->users()
            ->where('user_id', $user->id)
            ->first();

        return $pivot && in_array($pivot->pivot->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        // Only owners can delete workspaces
        $pivot = $workspace->users()
            ->where('user_id', $user->id)
            ->first();

        return $pivot && $pivot->pivot->role === 'owner';
    }

    /**
     * Determine whether the user can restore the workspace.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        // Only owners can restore workspaces
        $pivot = $workspace->users()
            ->where('user_id', $user->id)
            ->first();

        return $pivot && $pivot->pivot->role === 'owner';
    }

    /**
     * Determine whether the user can permanently delete the workspace.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        // Only owners can force delete workspaces
        $pivot = $workspace->users()
            ->where('user_id', $user->id)
            ->first();

        return $pivot && $pivot->pivot->role === 'owner';
    }
}
