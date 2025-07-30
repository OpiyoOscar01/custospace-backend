<?php

namespace App\Policies;

use App\Models\TimeLog;
use App\Models\User;

/**
 * Class TimeLogPolicy
 * 
 * Handles authorization for time log operations
 */
class TimeLogPolicy
{
    /**
     * Determine whether the user can view any time logs.
     */
    public function viewAny(User $user): bool
    {
        // Allow if user has admin role or can manage time logs
        return $user->hasPermission('view_time_logs') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the time log.
     */
    public function view(User $user, TimeLog $timeLog): bool
    {
        // User can view their own time logs or if they have permission
        return $user->id === $timeLog->user_id || 
               $user->hasPermission('view_all_time_logs') || 
               $user->isAdmin();
    }

    /**
     * Determine whether the user can create time logs.
     */
    public function create(User $user): bool
    {
        // Allow if user has permission to create time logs
        return $user->hasPermission('create_time_logs') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the time log.
     */
    public function update(User $user, TimeLog $timeLog): bool
    {
        // User can update their own time logs or if they have permission
        return $user->id === $timeLog->user_id || 
               $user->hasPermission('update_all_time_logs') || 
               $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the time log.
     */
    public function delete(User $user, TimeLog $timeLog): bool
    {
        // User can delete their own time logs or if they have permission
        return $user->id === $timeLog->user_id || 
               $user->hasPermission('delete_all_time_logs') || 
               $user->isAdmin();
    }
}
