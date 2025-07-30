<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Goal Policy
 * 
 * Handles authorization for goal operations
 */
class GoalPolicy
{
    /**
     * Determine whether the user can view any goals.
     */
    public function viewAny(User $user): bool
    {
        // Users can view goals if they have access to the workspace
        return true; // This should be refined based on your workspace access logic
    }

    /**
     * Determine whether the user can view the goal.
     */
    public function view(User $user, Goal $goal): bool
    {
        // Users can view goals if they are:
        // 1. The owner of the goal
        // 2. Member of the workspace
        // 3. Member of the team (if goal is assigned to a team)
        
        if ($user->id === $goal->owner_id) {
            return true;
        }

        // Check workspace membership (assuming you have a workspace_users table)
        if ($user->workspaces()->where('workspace_id', $goal->workspace_id)->exists()) {
            return true;
        }

        // Check team membership (if goal is assigned to a team)
        if ($goal->team_id && $user->teams()->where('team_id', $goal->team_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create goals.
     */
    public function create(User $user): bool
    {
        // Users can create goals if they have workspace access
        // This should be refined based on your business logic
        return true;
    }

    /**
     * Determine whether the user can update the goal.
     */
    public function update(User $user, Goal $goal): bool
    {
        // Users can update goals if they are:
        // 1. The owner of the goal
        // 2. Admin of the workspace
        // 3. Team lead (if goal is assigned to their team)
        
        if ($user->id === $goal->owner_id) {
            return true;
        }

        // Check if user is workspace admin (assuming you have workspace roles)
        if ($user->workspaceRoles()
                ->where('workspace_id', $goal->workspace_id)
                ->where('role', 'admin')
                ->exists()) {
            return true;
        }

        // Check if user is team lead for the goal's team
        if ($goal->team_id && 
            $user->teamRoles()
                ->where('team_id', $goal->team_id)
                ->where('role', 'lead')
                ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the goal.
     */
    public function delete(User $user, Goal $goal): bool
    {
        // Users can delete goals if they are:
        // 1. The owner of the goal
        // 2. Admin of the workspace
        
        if ($user->id === $goal->owner_id) {
            return true;
        }

        // Check if user is workspace admin
        if ($user->workspaceRoles()
                ->where('workspace_id', $goal->workspace_id)
                ->where('role', 'admin')
                ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the goal.
     */
    public function restore(User $user, Goal $goal): bool
    {
        return $this->delete($user, $goal);
    }

    /**
     * Determine whether the user can permanently delete the goal.
     */
    public function forceDelete(User $user, Goal $goal): bool
    {
        // Only workspace admins can permanently delete goals
        return $user->workspaceRoles()
                   ->where('workspace_id', $goal->workspace_id)
                   ->where('role', 'admin')
                   ->exists();
    }

    /**
     * Determine whether the user can activate the goal.
     */
    public function activate(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    /**
     * Determine whether the user can complete the goal.
     */
    public function complete(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    /**
     * Determine whether the user can cancel the goal.
     */
    public function cancel(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    /**
     * Determine whether the user can assign users to the goal.
     */
    public function assignUser(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    /**
     * Determine whether the user can assign tasks to the goal.
     */
    public function assignTasks(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }
}