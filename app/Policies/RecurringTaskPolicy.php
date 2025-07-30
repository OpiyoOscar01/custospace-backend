<?php

namespace App\Policies;

use App\Models\RecurringTask;
use App\Models\User;

/**
 * Class RecurringTaskPolicy
 * 
 * Handles authorization for recurring task operations
 */
class RecurringTaskPolicy
{
    /**
     * Determine whether the user can view any recurring tasks.
     */
    public function viewAny(User $user): bool
    {
        // Allow if user has permission to view recurring tasks
        return $user->hasPermission('view_recurring_tasks') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the recurring task.
     */
    public function view(User $user, RecurringTask $recurringTask): bool
    {
        // Check if user can access the associated task
        return $this->canAccessTask($user, $recurringTask) || 
               $user->hasPermission('view_all_recurring_tasks') || 
               $user->isAdmin();
    }

    /**
     * Determine whether the user can create recurring tasks.
     */
    public function create(User $user): bool
    {
        // Allow if user has permission to create recurring tasks
        return $user->hasPermission('create_recurring_tasks') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the recurring task.
     */
    public function update(User $user, RecurringTask $recurringTask): bool
    {
        // Check if user can access the associated task
        return $this->canAccessTask($user, $recurringTask) || 
               $user->hasPermission('update_all_recurring_tasks') || 
               $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the recurring task.
     */
    public function delete(User $user, RecurringTask $recurringTask): bool
    {
        // Check if user can access the associated task
        return $this->canAccessTask($user, $recurringTask) || 
               $user->hasPermission('delete_all_recurring_tasks') || 
               $user->isAdmin();
    }

    /**
     * Check if user can access the associated task.
     */
    protected function canAccessTask(User $user, RecurringTask $recurringTask): bool
    {
        $task = $recurringTask->task;
        
        // User can access if they own the task or are assigned to it
        return $task->created_by === $user->id || 
               $task->assigned_to === $user->id ||
               // If task belongs to a project the user has access to
               ($task->project && $user->hasAccessToProject($task->project_id));
    }
}
