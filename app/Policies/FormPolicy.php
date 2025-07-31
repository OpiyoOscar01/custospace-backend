<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Form Policy
 * 
 * Handles authorization for form operations
 */
class FormPolicy
{
    /**
     * Determine whether the user can view any forms.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view forms in their accessible workspaces
    }

    /**
     * Determine whether the user can view the form.
     */
    public function view(?User $user, Form $form): bool
    {
        // Public forms can be viewed by anyone if active
        if (!$user && $form->is_active) {
            return true;
        }

        // Authenticated users can view forms in their workspaces
        if ($user) {
            return $this->userCanAccessWorkspace($user, $form->workspace_id);
        }

        return false;
    }

    /**
     * Determine whether the user can create forms.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create forms
    }

    /**
     * Determine whether the user can update the form.
     */
    public function update(User $user, Form $form): bool
    {
        // Only form creator or workspace admins can update
        return $form->created_by_id === $user->id || 
               $this->userIsWorkspaceAdmin($user, $form->workspace_id);
    }

    /**
     * Determine whether the user can delete the form.
     */
    public function delete(User $user, Form $form): bool
    {
        // More restrictive - only form creator or workspace owners
        return $form->created_by_id === $user->id || 
               $this->userIsWorkspaceOwner($user, $form->workspace_id);
    }

    /**
     * Determine whether the user can restore the form.
     */
    public function restore(User $user, Form $form): bool
    {
        return $this->delete($user, $form);
    }

    /**
     * Determine whether the user can permanently delete the form.
     */
    public function forceDelete(User $user, Form $form): bool
    {
        return $this->userIsWorkspaceOwner($user, $form->workspace_id);
    }

    /**
     * Determine whether the user can duplicate the form.
     */
    public function duplicate(User $user, Form $form): bool
    {
        return $this->view($user, $form) && $this->create($user);
    }

    /**
     * Determine whether the user can export form data.
     */
    public function export(User $user, Form $form): bool
    {
        return $this->update($user, $form);
    }

    /**
     * Check if user can access the workspace
     */
    private function userCanAccessWorkspace(User $user, int $workspaceId): bool
    {
        // This should be implemented based on your workspace membership logic
        return $user->workspaces()->where('workspace_id', $workspaceId)->exists();
    }

    /**
     * Check if user is workspace admin
     */
    private function userIsWorkspaceAdmin(User $user, int $workspaceId): bool
    {
        // This should be implemented based on your workspace role logic
        return $user->workspaces()
                    ->where('workspace_id', $workspaceId)
                    ->wherePivot('role', 'admin')
                    ->exists();
    }

    /**
     * Check if user is workspace owner
     */
    private function userIsWorkspaceOwner(User $user, int $workspaceId): bool
    {
        // This should be implemented based on your workspace ownership logic
        return $user->workspaces()
                    ->where('workspace_id', $workspaceId)
                    ->wherePivot('role', 'owner')
                    ->exists();
    }
}
