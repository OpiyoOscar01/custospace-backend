<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tasks.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Users can view tasks in workspaces they belong to
        // This can be customized based on your permissions system
        return true;
    }

    /**
     * Determine whether the user can view the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        // Users can view tasks in workspaces they belong to
        // This can be customized based on your workspace membership or permissions system
        return $this->userBelongsToWorkspace($user, $task->workspace_id);
    }

    /**
     * Determine whether the user can create tasks.
     *
     * @param User $user
     * @param int $workspaceId
     * @return bool
     */
    public function create(User $user, int $workspaceId = null): bool
    {
        if ($workspaceId) {
            return $this->userBelongsToWorkspace($user, $workspaceId);
        }
        
        // If no workspace ID is provided, check if the user can create tasks in any workspace
        // This depends on your application's permission structure
        return true;
    }

    /**
     * Determine whether the user can update the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        // Users can update tasks if they're the assignee, reporter, or have proper workspace permissions
        return $this->userBelongsToWorkspace($user, $task->workspace_id) &&
               ($user->id === $task->assignee_id || 
                $user->id === $task->reporter_id || 
                $this->userHasEditPermission($user, $task->workspace_id));
    }

    /**
     * Determine whether the user can delete the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function delete(User $user, Task $task): bool
    {
        // Only users with proper workspace permissions or task reporters can delete tasks
        return $this->userBelongsToWorkspace($user, $task->workspace_id) &&
               ($user->id === $task->reporter_id || 
                $this->userHasDeletePermission($user, $task->workspace_id));
    }

    /**
     * Check if user belongs to workspace.
     *
     * @param User $user
     * @param int $workspaceId
     * @return bool
     */
    private function userBelongsToWorkspace(User $user, int $workspaceId): bool
    {
        // Implement based on your application's workspace membership system
        // Example: return $user->workspaces->contains($workspaceId);
        return true; // Placeholder - replace with actual implementation
    }

    /**
     * Check if user has edit permission in workspace.
     *
     * @param User $user
     * @param int $workspaceId
     * @return bool
     */
    private function userHasEditPermission(User $user, int $workspaceId): bool
    {
        // Implement based on your application's permission system
        // Example: return $user->hasWorkspacePermission($workspaceId, 'edit_tasks');
        return true; // Placeholder - replace with actual implementation
    }

    /**
     * Check if user has delete permission in workspace.
     *
     * @param User $user
     * @param int $workspaceId
     * @return bool
     */
    private function userHasDeletePermission(User $user, int $workspaceId): bool
    {
        // Implement based on your application's permission system
        // Example: return $user->hasWorkspacePermission($workspaceId, 'delete_tasks');
        return true; // Placeholder - replace with actual implementation
    }
}
