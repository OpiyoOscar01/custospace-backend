<?php
// app/Policies/PipelinePolicy.php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Pipeline Policy
 * 
 * Handles authorization logic for pipeline operations.
 */
class PipelinePolicy
{
    /**
     * Determine whether the user can view any pipelines.
     */
    public function viewAny(User $user): bool
    {
        // User can view pipelines if they have access to any workspace
        return $user->workspaces()->exists();
    }

    /**
     * Determine whether the user can view the pipeline.
     */
    public function view(User $user, Pipeline $pipeline): bool
    {
        // User can view pipeline if they have access to the workspace
        return $this->hasWorkspaceAccess($user, $pipeline->workspace_id) &&
               $this->canAccessProject($user, $pipeline);
    }

    /**
     * Determine whether the user can create pipelines.
     */
    public function create(User $user): bool
    {
        // User can create pipelines if they are an admin in at least one workspace
        return $user->workspaces()->wherePivot('role', 'admin')->exists() ||
               $user->workspaces()->wherePivot('role', 'owner')->exists();
    }

    /**
     * Determine whether the user can update the pipeline.
     */
    public function update(User $user, Pipeline $pipeline): bool
    {
        // User can update pipeline if they are a workspace admin/owner
        // and if project-specific, they have project access
        return $this->isWorkspaceAdmin($user, $pipeline->workspace_id) &&
               $this->canAccessProject($user, $pipeline);
    }

    /**
     * Determine whether the user can delete the pipeline.
     */
    public function delete(User $user, Pipeline $pipeline): bool
    {
        // User can delete pipeline if they are a workspace admin/owner
        // Default pipeline cannot be deleted if it's the only one
        return !($pipeline->is_default && $this->isOnlyPipeline($pipeline)) &&
               $this->isWorkspaceAdmin($user, $pipeline->workspace_id) &&
               $this->canAccessProject($user, $pipeline);
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
    
    /**
     * Check if user can access the project if this pipeline is project-specific.
     */
    private function canAccessProject(User $user, Pipeline $pipeline): bool
    {
        if (!$pipeline->project_id) {
            return true; // Not project-specific
        }
        
        $project = $pipeline->project;
        
        // User can access if they are:
        // 1. Project owner
        if ($user->id === $project->owner_id) {
            return true;
        }
        
        // 2. Project team member
        if ($project->users()->where('user_id', $user->id)->exists()) {
            return true;
        }
        
        // 3. Team member (if project has team)
        if ($project->team_id && $project->team->users()->where('user_id', $user->id)->exists()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if this is the only pipeline in its context.
     */
    private function isOnlyPipeline(Pipeline $pipeline): bool
    {
        $query = Pipeline::query();
        
        if ($pipeline->project_id) {
            $query->where('project_id', $pipeline->project_id);
        } else {
            $query->where('workspace_id', $pipeline->workspace_id)
                  ->whereNull('project_id');
        }
        
        return $query->count() === 1;
    }
}
