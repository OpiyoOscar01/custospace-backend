<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\User;

/**
 * Integration Policy
 * 
 * Defines authorization rules for integration operations
 */
class IntegrationPolicy
{
    /**
     * Determine whether the user can view any integrations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('integrations.view');
    }

    /**
     * Determine whether the user can view the integration.
     */
    public function view(User $user, Integration $integration): bool
    {
        return $user->hasPermission('integrations.view') && 
               $user->canAccessWorkspace($integration->workspace_id);
    }

    /**
     * Determine whether the user can create integrations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('integrations.create');
    }

    /**
     * Determine whether the user can update the integration.
     */
    public function update(User $user, Integration $integration): bool
    {
        return $user->hasPermission('integrations.update') && 
               $user->canAccessWorkspace($integration->workspace_id);
    }

    /**
     * Determine whether the user can delete the integration.
     */
    public function delete(User $user, Integration $integration): bool
    {
        return $user->hasPermission('integrations.delete') && 
               $user->canAccessWorkspace($integration->workspace_id);
    }

    /**
     * Determine whether the user can restore the integration.
     */
    public function restore(User $user, Integration $integration): bool
    {
        return $user->hasPermission('integrations.restore');
    }

    /**
     * Determine whether the user can permanently delete the integration.
     */
    public function forceDelete(User $user, Integration $integration): bool
    {
        return $user->hasPermission('integrations.force-delete');
    }
}
