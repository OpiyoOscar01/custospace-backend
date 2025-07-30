<?php
// app/Policies/ReactionPolicy.php

namespace App\Policies;

use App\Models\Reaction;
use App\Models\User;

/**
 * Reaction Authorization Policy
 * 
 * Defines authorization rules for reaction operations
 */
class ReactionPolicy
{
    /**
     * Determine whether the user can view any reactions.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view reactions
        return true;
    }

    /**
     * Determine whether the user can view the reaction.
     */
    public function view(User $user, Reaction $reaction): bool
    {
        // All authenticated users can view individual reactions
        return true;
    }

    /**
     * Determine whether the user can create reactions.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create reactions
        return true;
    }

    /**
     * Determine whether the user can update the reaction.
     */
    public function update(User $user, Reaction $reaction): bool
    {
        // Users can only update their own reactions
        return $user->id === $reaction->user_id;
    }

    /**
     * Determine whether the user can delete the reaction.
     */
    public function delete(User $user, Reaction $reaction): bool
    {
        // Users can delete their own reactions, or admins can delete any
        return $user->id === $reaction->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('manage_reactions');
    }

    /**
     * Determine whether the user can toggle reactions.
     */
    public function toggle(User $user): bool
    {
        // All authenticated users can toggle reactions
        return true;
    }

    /**
     * Determine whether the user can bulk toggle reactions.
     */
    public function bulkToggle(User $user): bool
    {
        // All authenticated users can bulk toggle their own reactions
        return true;
    }
}
