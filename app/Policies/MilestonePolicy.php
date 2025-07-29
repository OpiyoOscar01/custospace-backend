<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MilestonePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any milestones.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Users can view milestones in projects they have access to
        return true;
    }

    /**
     * Determine whether the user can view the milestone.
     *
     * @param User $user
     * @param Milestone $milestone
     * @return bool
     */
    public function view(User $user, Milestone $milestone): bool
    {
        // Users can view milestones in projects they have access to
        return $this->userHasProjectAccess($user, $milestone->project_id);
    }

    /**
     * Determine whether the user can create milestones.
     *
     * @param User $user
     * @param int $projectId
     * @return bool
     */
    public function create(User $user, int $projectId): bool
    {
        return $this->userHasProjectAccess($user, $projectId);
    }

    /**
     * Determine whether the user can update the milestone.
     *
     * @param User $user
     * @param Milestone $milestone
     * @return bool
     */
    public function update(User $user, Milestone $milestone): bool
    {
        return $this->userHasProjectAccess($user, $milestone->project_id);
    }

    /**
     * Determine whether the user can delete the milestone.
     *
     * @param User $user
     * @param Milestone $milestone
     * @return bool
     */
    public function delete(User $user, Milestone $milestone): bool
    {
        // Only users with proper project permissions can delete milestones
        return $this->userHasProjectAccess($user, $milestone->project_id) && 
               $this->userHasDeletePermission($user, $milestone->project_id);
    }

    /**
     * Check if user has access to the project.
     *
     * @param User $user
     * @param int $projectId
     * @return bool
     */
    private function userHasProjectAccess(User $user, int $projectId): bool
    {
        // Implement based on your application's project membership system
        // Example: return $user->projects->contains($projectId);
        return true; // Placeholder - replace with actual implementation
    }

    /**
     * Check if user has delete permission for the project.
     *
     * @param User $user
     * @param int $projectId
     * @return bool
     */
    private function userHasDeletePermission(User $user, int $projectId): bool
    {
        // Implement based on your application's permission system
        // Example: return $user->hasProjectPermission($projectId, 'delete_milestones');
        return true; // Placeholder - replace with actual implementation
    }
}
