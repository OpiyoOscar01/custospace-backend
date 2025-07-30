<?php
// app/Policies/ActivityLogPolicy.php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Activity Log Authorization Policy
 * 
 * Defines authorization rules for activity log operations
 */
class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        // Users can view activity logs in their accessible workspaces
        return $user->hasAnyPermission(['view_activity_logs', 'manage_activity_logs']);
    }

    /**
     * Determine whether the user can view the activity log.
     */
    public function view(User $user, ActivityLog $activityLog): bool
    {
        // Users can view activity logs in workspaces they have access to
        return $user->hasPermissionInWorkspace('view_activity_logs', $activityLog->workspace_id) ||
               $user->hasPermissionInWorkspace('manage_activity_logs', $activityLog->workspace_id);
    }

    /**
     * Determine whether the user can create activity logs.
     */
    public function create(User $user): bool
    {
        // Users with create permission can create activity logs
        return $user->hasAnyPermission(['create_activity_logs', 'manage_activity_logs']);
    }

    /**
     * Determine whether the user can update the activity log.
     */
    public function update(User $user, ActivityLog $activityLog): bool
    {
        // Only users with manage permission can update activity logs
        return $user->hasPermissionInWorkspace('manage_activity_logs', $activityLog->workspace_id);
    }

    /**
     * Determine whether the user can delete the activity log.
     */
    public function delete(User $user, ActivityLog $activityLog): bool
    {
        // Only users with manage permission can delete activity logs
        return $user->hasPermissionInWorkspace('manage_activity_logs', $activityLog->workspace_id);
    }

    /**
     * Determine whether the user can view sensitive information.
     */
    public function viewSensitive(User $user, ActivityLog $activityLog): bool
    {
        // Only admins or users with special permission can view sensitive data
        return $user->hasRole('admin') ||
               $user->hasPermissionInWorkspace('view_sensitive_logs', $activityLog->workspace_id);
    }

    /**
     * Determine whether the user can cleanup old activity logs.
     */
    public function cleanup(User $user): bool
    {
        // Only admins can cleanup old logs
        return $user->hasRole('admin') || $user->hasPermission('cleanup_activity_logs');
    }
}
