<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

/**
 * Backup Policy
 * 
 * Handles authorization for backup operations
 */
class BackupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow users to view backups in their workspaces
        return $user->hasPermission('backup.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Backup $backup): bool
    {
        // Allow if user has access to the workspace
        return $user->hasAccessToWorkspace($backup->workspace_id) && 
               $user->hasPermission('backup.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('backup.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Backup $backup): bool
    {
        return $user->hasAccessToWorkspace($backup->workspace_id) && 
               $user->hasPermission('backup.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Backup $backup): bool
    {
        return $user->hasAccessToWorkspace($backup->workspace_id) && 
               $user->hasPermission('backup.delete');
    }
}
