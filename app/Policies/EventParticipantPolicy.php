<?php

namespace App\Policies;

use App\Models\EventParticipant;
use App\Models\User;

/**
 * Event Participant Policy
 * 
 * Handles authorization for event participant operations
 */
class EventParticipantPolicy
{
    /**
     * Determine whether the user can view any event participants.
     */
    public function viewAny(User $user): bool
    {
        return true; // Additional checks done at controller level
    }

    /**
     * Determine whether the user can view the event participant.
     */
    public function view(User $user, EventParticipant $participant): bool
    {
        // Users can view participant information if they:
        // 1. Are the event creator
        // 2. Are a participant in the same event
        // 3. Are the participant themselves
        return $user->id === $participant->event->created_by_id ||
               $participant->event->participants()->where('user_id', $user->id)->exists() ||
               $user->id === $participant->user_id;
    }

    /**
     * Determine whether the user can create event participants.
     */
    public function create(User $user): bool
    {
        return true; // Additional checks done at controller level
    }

    /**
     * Determine whether the user can update the event participant.
     */
    public function update(User $user, EventParticipant $participant): bool
    {
        // Users can update participant status if they:
        // 1. Are the event creator
        // 2. Are the participant themselves
        return $user->id === $participant->event->created_by_id ||
               $user->id === $participant->user_id;
    }

    /**
     * Determine whether the user can delete the event participant.
     */
    public function delete(User $user, EventParticipant $participant): bool
    {
        // Users can delete participants if they:
        // 1. Are the event creator
        // 2. Are removing themselves from the event
        return $user->id === $participant->event->created_by_id ||
               $user->id === $participant->user_id;
    }

    /**
     * Determine whether the user can restore the event participant.
     */
    public function restore(User $user, EventParticipant $participant): bool
    {
        return $user->id === $participant->event->created_by_id;
    }

    /**
     * Determine whether the user can permanently delete the event participant.
     */
    public function forceDelete(User $user, EventParticipant $participant): bool
    {
        return $user->id === $participant->event->created_by_id;
    }
}