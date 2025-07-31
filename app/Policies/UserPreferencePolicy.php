<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPreference;

/**
 * Class UserPreferencePolicy
 * 
 * Handles authorization for UserPreference operations
 */
class UserPreferencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admins to view all preferences, users can view their own
        return $user->hasRole('admin') || true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserPreference $userPreference): bool
    {
        // Users can view their own preferences, admins can view all
        return $user->id === $userPreference->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create preferences
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserPreference $userPreference): bool
    {
        // Users can update their own preferences, admins can update all
        return $user->id === $userPreference->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserPreference $userPreference): bool
    {
        // Users can delete their own preferences, admins can delete all
        return $user->id === $userPreference->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserPreference $userPreference): bool
    {
        return $this->update($user, $userPreference);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserPreference $userPreference): bool
    {
        return $user->hasRole('admin');
    }
}
