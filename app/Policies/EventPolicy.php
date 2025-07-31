<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;

/**
 * Event Policy
 * 
 * Handles authorization for event operations
 */
class EventPolicy
{
    /**
     * Determine whether the user can view any events.
     */
    public function viewAny(User $user): bool
    {
        // Users can view events in workspaces they belong to
        return true; // Additional workspace membership check should be done in controller
    }

    /**
     * Determine whether the user can view the event.
     */
    public function view(User $user, Event $event): bool
    {
        // Users can view events if they:
        // 1. Created the event
        // 2. Are participants in the event
        // 3. Are members of the workspace (to be checked at controller level)
        return $user->id === $event->created_by_id ||
               $event->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create events.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create events
        // Workspace membership should be validated in the controller
        return true;
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        // Only the event creator can update the event
        return $user->id === $event->created_by_id;
    }

    /**
     * Determine whether the user can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        // Only the event creator can delete the event
        return $user->id === $event->created_by_id;
    }

    /**
     * Determine whether the user can update participant status.
     */
    public function updateParticipant(User $user, Event $event, EventParticipant $participant): bool
    {
        // Users can update participant status if they:
        // 1. Are the event creator (can update any participant)
        // 2. Are the participant themselves (can update their own status)
        return $user->id === $event->created_by_id || $user->id === $participant->user_id;
    }

    /**
     * Determine whether the user can restore the event.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->id === $event->created_by_id;
    }

    /**
     * Determine whether the user can permanently delete the event.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->id === $event->created_by_id;
    }
}