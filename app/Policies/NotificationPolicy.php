<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Notification Policy
 * 
 * Defines authorization rules for notification operations
 */
class NotificationPolicy
{
    /**
     * Determine whether the user can view any notifications.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('view_notifications');
    }

    /**
     * Determine whether the user can view the notification.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('view_all_notifications');
    }

    /**
     * Determine whether the user can create notifications.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_notifications') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the notification.
     */
    public function update(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('update_all_notifications');
    }

    /**
     * Determine whether the user can delete the notification.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id || 
               $user->hasRole('admin') || 
               $user->hasPermission('delete_all_notifications');
    }

    /**
     * Determine whether the user can restore the notification.
     */
    public function restore(User $user, Notification $notification): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the notification.
     */
    public function forceDelete(User $user, Notification $notification): bool
    {
        return $user->hasRole('admin');
    }
}
