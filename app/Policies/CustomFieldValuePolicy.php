<?php

namespace App\Policies;

use App\Models\CustomFieldValue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Custom Field Value Policy
 * 
 * Handles authorization for custom field value operations
 */
class CustomFieldValuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_custom_field_values');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $user->can('view_custom_field_values') && 
               $user->hasAccessToWorkspace($customFieldValue->customField->workspace_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_custom_field_values');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $user->can('update_custom_field_values') && 
               $user->hasAccessToWorkspace($customFieldValue->customField->workspace_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $user->can('delete_custom_field_values') && 
               $user->hasAccessToWorkspace($customFieldValue->customField->workspace_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $user->can('restore_custom_field_values') && 
               $user->hasAccessToWorkspace($customFieldValue->customField->workspace_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomFieldValue $customFieldValue): bool
    {
        return $user->can('force_delete_custom_field_values') && 
               $user->hasAccessToWorkspace($customFieldValue->customField->workspace_id);
    }
}
