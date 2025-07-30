<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Reminder Policy
 * 
 * Defines authorization rules for reminder operations
 */
class ReminderPolicy
{
    /**
     * Determine whether the user can view any reminders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('view_reminders');
    }

    /**
     * Determine whether the user can view the reminder.
     */
    public function view(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('view_all_reminders');
    }

    /**
     * Determine whether the user can create reminders.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_reminders') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the reminder.
     */
    public function update(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('update_all_reminders');
    }

    /**
     * Determine whether the user can delete the reminder.
     */
    public function delete(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('delete_all_reminders');
    }

    /**
     * Determine whether the user can restore the reminder.
     */
    public function restore(User $user, Reminder $reminder): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the reminder.
     */
    public function forceDelete(User $user, Reminder $reminder): bool
    {
        return $user->hasRole('admin');
    }
}
