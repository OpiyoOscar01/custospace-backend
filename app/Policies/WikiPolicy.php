<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wiki;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Wiki Policy - Handles authorization for wiki operations
 */
class WikiPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any wikis.
     */
    public function viewAny(User $user): bool
    {
        // Users can view wikis if they have access to any workspace
        return $user->workspaces()->exists();
    }

    /**
     * Determine whether the user can view the wiki.
     */
    public function view(User $user, Wiki $wiki): bool
    {
        // Check if user has access to the workspace
        if ($this->hasWorkspaceAccess($user, $wiki->workspace_id)) {
            return true;
        }

        // Check if user is a collaborator on this wiki
        if ($this->isCollaborator($user, $wiki)) {
            return true;
        }

        // Check if wiki is published and workspace is public
        return $wiki->is_published && $this->isWorkspacePublic($wiki->workspace_id);
    }

    /**
     * Determine whether the user can create wikis.
     */
    public function create(User $user): bool
    {
        // Users can create wikis if they have access to at least one workspace
        return $user->workspaces()->exists();
    }

    /**
     * Determine whether the user can update the wiki.
     */
    public function update(User $user, Wiki $wiki): bool
    {
        // Wiki creator can always update
        if ($user->id === $wiki->created_by_id) {
            return true;
        }

        // Workspace admin can update
        if ($this->isWorkspaceAdmin($user, $wiki->workspace_id)) {
            return true;
        }

        // Check if user is a collaborator with edit permissions
        return $this->canCollaboratorEdit($user, $wiki);
    }

    /**
     * Determine whether the user can delete the wiki.
     */
    public function delete(User $user, Wiki $wiki): bool
    {
        // Wiki creator can delete
        if ($user->id === $wiki->created_by_id) {
            return true;
        }

        // Workspace admin can delete
        return $this->isWorkspaceAdmin($user, $wiki->workspace_id);
    }

    /**
     * Determine whether the user can restore the wiki.
     */
    public function restore(User $user, Wiki $wiki): bool
    {
        return $this->delete($user, $wiki);
    }

    /**
     * Determine whether the user can permanently delete the wiki.
     */
    public function forceDelete(User $user, Wiki $wiki): bool
    {
        // Only workspace admin can force delete
        return $this->isWorkspaceAdmin($user, $wiki->workspace_id);
    }

    /**
     * Check if user has access to workspace.
     */
    private function hasWorkspaceAccess(User $user, int $workspaceId): bool
    {
        return $user->workspaces()
            ->where('workspace_id', $workspaceId)
            ->exists();
    }

    /**
     * Check if user is workspace admin.
     */
    private function isWorkspaceAdmin(User $user, int $workspaceId): bool
    {
        return $user->workspaces()
            ->where('workspace_id', $workspaceId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Check if user is a collaborator on the wiki.
     */
    private function isCollaborator(User $user, Wiki $wiki): bool
    {
        $collaborators = $wiki->metadata['collaborators'] ?? [];
        
        return collect($collaborators)
            ->contains('user_id', $user->id);
    }

    /**
     * Check if collaborator can edit the wiki.
     */
    private function canCollaboratorEdit(User $user, Wiki $wiki): bool
    {
        $collaborators = $wiki->metadata['collaborators'] ?? [];
        
        $collaborator = collect($collaborators)
            ->firstWhere('user_id', $user->id);

        if (!$collaborator) {
            return false;
        }

        return in_array($collaborator['role'], ['editor', 'collaborator']);
    }

    /**
     * Check if workspace is public (placeholder implementation).
     */
    private function isWorkspacePublic(int $workspaceId): bool
    {
        // This would check workspace settings
        // For now, assume all workspaces are private
        return false;
    }
}
