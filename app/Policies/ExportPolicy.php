<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Export Policy
 * 
 * Defines authorization rules for Export operations
 */
class ExportPolicy
{
    /**
     * Determine whether the user can view any exports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view exports');
    }

    /**
     * Determine whether the user can view the export.
     */
    public function view(User $user, Export $export): bool
    {
        // User can view if they belong to the same workspace
        return $user->workspaces()->where('workspace_id', $export->workspace_id)->exists() ||
               $user->hasPermissionTo('view all exports');
    }

    /**
     * Determine whether the user can create exports.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create exports');
    }

    /**
     * Determine whether the user can update the export.
     */
    public function update(User $user, Export $export): bool
    {
        // User can update if they own the export or have permission
        return $export->user_id === $user->id ||
               $user->hasPermissionTo('update all exports');
    }

    /**
     * Determine whether the user can delete the export.
     */
    public function delete(User $user, Export $export): bool
    {
        // User can delete if they own the export or have permission
        return $export->user_id === $user->id ||
               $user->hasPermissionTo('delete all exports');
    }

    /**
     * Determine whether the user can restore the export.
     */
    public function restore(User $user, Export $export): bool
    {
        return $user->hasPermissionTo('restore exports');
    }

    /**
     * Determine whether the user can permanently delete the export.
     */
    public function forceDelete(User $user, Export $export): bool
    {
        return $user->hasPermissionTo('force delete exports');
    }
}
