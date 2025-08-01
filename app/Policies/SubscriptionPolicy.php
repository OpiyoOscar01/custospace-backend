<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

/**
 * Class SubscriptionPolicy
 * 
 * Authorization policies for subscription operations
 * 
 * @package App\Policies
 */
class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subscriptions.view');
    }

    /**
     * Determine whether the user can view the subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('subscriptions.view') 
            && $user->canAccessWorkspace($subscription->workspace_id);
    }

    /**
     * Determine whether the user can create subscriptions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('subscriptions.create');
    }

    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('subscriptions.update') 
            && $user->canAccessWorkspace($subscription->workspace_id);
    }

    /**
     * Determine whether the user can delete the subscription.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('subscriptions.delete') 
            && $user->canAccessWorkspace($subscription->workspace_id);
    }

    /**
     * Determine whether the user can restore the subscription.
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('subscriptions.restore');
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('subscriptions.force-delete');
    }
}
