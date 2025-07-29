<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tags.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // User can view tags in workspaces they have access to
        return true;
    }

    /**
     * Determine whether the user can view the tag.
     *
     * @param User $user
     * @param Tag $tag
     * @return bool
     */
    public function view(User $user, Tag $tag): bool
    {
        // Check if user has access to the workspace this tag belongs to
        return $this->checkWorkspaceAccess($user, $tag);
    }

    /**
     * Determine whether the user can create tags.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Users can create tags in workspaces they have access to
        // Specific workspace access will be validated in the request
        return true;
    }

    /**
     * Determine whether the user can update the tag.
     *
     * @param User $user
     * @param Tag $tag
     * @return bool
     */
    public function update(User $user, Tag $tag): bool
    {
        return $this->checkWorkspaceAccess($user, $tag);
    }

    /**
     * Determine whether the user can delete the tag.
     *
     * @param User $user
     * @param Tag $tag
     * @return bool
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $this->checkWorkspaceAccess($user, $tag);
    }

    /**
     * Helper method to check if a user has access to a tag's workspace.
     *
     * @param User $user
     * @param Tag $tag
     * @return bool
     */
    protected function checkWorkspaceAccess(User $user, Tag $tag): bool
    {
        // This would depend on your workspace access control system
        // For example, check if user belongs to workspace or has role in workspace
        return $user->workspaces->contains($tag->workspace_id);
    }
}
