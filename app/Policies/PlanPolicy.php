<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

/**
 * Plan Policy
 * 
 * Defines authorization rules for plan operations
 */
class PlanPolicy
{
    /**
     * Determine whether the user can view any plans.
     */
    public function viewAny(User $user): bool
    {
        // Most users can view plans (for subscription purposes)
        return true;
    }

    /**
     * Determine whether the user can view the plan.
     */
    public function view(User $user, Plan $plan): bool
    {
        // Users can view active plans, admins can view all
        return $plan->is_active || $user->hasPermission('plans.view-all');
    }

    /**
     * Determine whether the user can create plans.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('plans.create');
    }

    /**
     * Determine whether the user can update the plan.
     */
    public function update(User $user, Plan $plan): bool
    {
        return $user->hasPermission('plans.update');
    }

    /**
     * Determine whether the user can delete the plan.
     */
    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasPermission('plans.delete');
    }

    /**
     * Determine whether the user can restore the plan.
     */
    public function restore(User $user, Plan $plan): bool
    {
        return $user->hasPermission('plans.restore');
    }

    /**
     * Determine whether the user can permanently delete the plan.
     */
    public function forceDelete(User $user, Plan $plan): bool
    {
        return $user->hasPermission('plans.force-delete');
    }
}
