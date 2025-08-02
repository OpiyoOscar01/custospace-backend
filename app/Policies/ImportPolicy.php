<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Import Policy
 * 
 * Defines authorization rules for Import operations
 */
class ImportPolicy
{
    /**
     * Determine whether the user can view any imports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view imports');
    }

    /**
     * Determine whether the user can view the import.
     */
    public function view(User $user, Import $import): bool
    {
        // User can view if they belong to the same workspace
        return $user->workspaces()->where('workspace_id', $import->workspace_id)->exists() ||
               $user->hasPermissionTo('view all imports');
    }

    /**
     * Determine whether the user can create imports.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create imports');
    }

    /**
     * Determine whether the user can update the import.
     */
    public function update(User $user, Import $import): bool
    {
        // User can update if they own the import or have permission
        return $import->user_id === $user->id ||
               $user->hasPermissionTo('update all imports');
    }

    /**
     * Determine whether the user can delete the import.
     */
    public function delete(User $user, Import $import): bool
    {
        // User can delete if they own the import or have permission
        return $import->user_id === $user->id ||
               $user->hasPermissionTo('delete all imports');
    }

    /**
     * Determine whether the user can restore the import.
     */
    public function restore(User $user, Import $import): bool
    {
        return $user->hasPermissionTo('restore imports');
    }

    /**
     * Determine whether the user can permanently delete the import.
     */
    public function forceDelete(User $user, Import $import): bool
    {
        return $user->hasPermissionTo('force delete imports');
    }
}
