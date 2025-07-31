<?php

namespace App\Policies;

use App\Models\ApiToken;
use App\Models\User;

/**
 * Class ApiTokenPolicy
 * 
 * Handles authorization for ApiToken operations
 */
class ApiTokenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow admins to view all tokens, users can view their own
        return $user->hasRole('admin') || true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApiToken $apiToken): bool
    {
        // Users can view their own tokens, admins can view all
        return $user->id === $apiToken->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create tokens
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApiToken $apiToken): bool
    {
        // Users can update their own tokens, admins can update all
        return $user->id === $apiToken->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApiToken $apiToken): bool
    {
        // Users can delete their own tokens, admins can delete all
        return $user->id === $apiToken->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ApiToken $apiToken): bool
    {
        return $this->update($user, $apiToken);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ApiToken $apiToken): bool
    {
        return $user->hasRole('admin');
    }
}
