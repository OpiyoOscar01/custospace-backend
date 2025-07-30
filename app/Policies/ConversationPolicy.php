<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any conversations.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view conversations they're part of
    }

    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        // User must be a member of the conversation
        return $conversation->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create conversations.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create conversations
        return true;
    }

    /**
     * Determine whether the user can update the conversation.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        // Only admins and owners can update conversation properties
        return $conversation->users()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Determine whether the user can delete the conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        // Only conversation owners can delete it
        return $conversation->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    /**
     * Determine whether the user can add users to the conversation.
     */
    public function addUsers(User $user, Conversation $conversation): bool
    {
        // Only admins and owners can add users
        return $conversation->users()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Determine whether the user can remove users from the conversation.
     */
    public function removeUsers(User $user, Conversation $conversation): bool
    {
        // Only admins and owners can remove users
        return $conversation->users()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Determine whether the user can update user roles in the conversation.
     */
    public function updateUserRole(User $user, Conversation $conversation): bool
    {
        // Only conversation owners can update roles
        return $conversation->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
    public function sendMessage(User $user, Conversation $conversation): bool
{
    // User must be a member of the conversation
    return $conversation->users()->where('users.id', $user->id)->exists();
}
}
