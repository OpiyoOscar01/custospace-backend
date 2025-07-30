<?php

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Custom Field Policy
 * 
 * Handles authorization for custom field operations
 */
class CustomFieldPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_custom_fields');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomField $customField): bool
    {
        return $user->can('view_custom_fields') && 
               $user->hasAccessToWorkspace($customField->workspace_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_custom_fields');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomField $customField): bool
    {
        return $user->can('update_custom_fields') && 
               $user->hasAccessToWorkspace($customField->workspace_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomField $customField): bool
    {
        return $user->can('delete_custom_fields') && 
               $user->hasAccessToWorkspace($customField->workspace_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomField $customField): bool
    {
        return $user->can('restore_custom_fields') && 
               $user->hasAccessToWorkspace($customField->workspace_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomField $customField): bool
    {
        return $user->can('force_delete_custom_fields') && 
               $user->hasAccessToWorkspace($customField->workspace_id);
    }
}
