<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    /**
     * Determine whether the user can view any teams.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view teams
    }

    /**
     * Determine whether the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        // Users can view teams if they belong to the workspace
        return $team->workspace->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create teams.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create teams (workspace level check done separately)
        return true;
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        // Check if user is team owner/admin or workspace owner/admin
        
        // Check team roles first
        $teamPivot = $team->users()
            ->where('user_id', $user->id)
            ->first();
            
        if ($teamPivot && in_array($teamPivot->pivot->role, ['owner', 'admin'])) {
            return true;
        }
        
        // Check workspace roles
        $workspacePivot = $team->workspace->users()
            ->where('user_id', $user->id)
            ->first();
            
        return $workspacePivot && in_array($workspacePivot->pivot->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        // Check if user is team owner or workspace owner
        
        // Check team roles first
        $teamPivot = $team->users()
            ->where('user_id', $user->id)
            ->first();
            
        if ($teamPivot && $teamPivot->pivot->role === 'owner') {
            return true;
        }
        
        // Check workspace roles
        $workspacePivot = $team->workspace->users()
            ->where('user_id', $user->id)
            ->first();
            
        return $workspacePivot && $workspacePivot->pivot->role === 'owner';
    }

    /**
     * Determine whether the user can restore the team.
     */
    public function restore(User $user, Team $team): bool
    {
        // Similar to delete permission
        return $this->delete($user, $team);
    }

    /**
     * Determine whether the user can permanently delete the team.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        // Only workspace owner can force delete
        $workspacePivot = $team->workspace->users()
            ->where('user_id', $user->id)
            ->first();
            
        return $workspacePivot && $workspacePivot->pivot->role === 'owner';
    }
}
