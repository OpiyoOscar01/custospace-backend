<?php

namespace App\Policies;

use App\Models\FormResponse;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Form Response Policy
 * 
 * Handles authorization for form response operations
 */
class FormResponsePolicy
{
    /**
     * Determine whether the user can view any form responses.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view responses for forms they have access to
    }

    /**
     * Determine whether the user can view the form response.
     */
    public function view(User $user, FormResponse $formResponse): bool
    {
        // Users can view their own responses
        if ($formResponse->user_id === $user->id) {
            return true;
        }

        // Form creators and workspace admins can view all responses
        return $formResponse->form->created_by_id === $user->id ||
               $this->userIsWorkspaceAdmin($user, $formResponse->form->workspace_id);
    }

    /**
     * Determine whether the user can create form responses.
     */
    public function create(?User $user, \App\Models\Form $form): bool
    {
        // Check if form is active
        if (!$form->is_active) {
            return false;
        }

        // Check authentication requirements
        $requiresAuth = $form->settings['require_authentication'] ?? false;
        if ($requiresAuth && !$user) {
            return false;
        }

        // Check multiple submission settings
        $allowMultiple = $form->settings['allow_multiple_submissions'] ?? false;
        if (!$allowMultiple && $user) {
            $existingResponse = $form->responses()->where('user_id', $user->id)->exists();
            if ($existingResponse) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the form response.
     */
    public function update(User $user, FormResponse $formResponse): bool
    {
        // Users can update their own responses within time limit
        if ($formResponse->user_id === $user->id) {
            $editTimeLimit = $formResponse->form->settings['edit_time_limit'] ?? 60; // minutes
            $canEdit = $formResponse->created_at->addMinutes($editTimeLimit)->isFuture();
            return $canEdit;
        }

        // Form creators and workspace admins can always update
        return $formResponse->form->created_by_id === $user->id ||
               $this->userIsWorkspaceAdmin($user, $formResponse->form->workspace_id);
    }

    /**
     * Determine whether the user can delete the form response.
     */
    public function delete(User $user, FormResponse $formResponse): bool
    {
        // Users can delete their own responses
        if ($formResponse->user_id === $user->id) {
            return true;
        }

        // Form creators and workspace admins can delete responses
        return $formResponse->form->created_by_id === $user->id ||
               $this->userIsWorkspaceAdmin($user, $formResponse->form->workspace_id);
    }

    /**
     * Determine whether the user can restore the form response.
     */
    public function restore(User $user, FormResponse $formResponse): bool
    {
        return $this->delete($user, $formResponse);
    }

    /**
     * Determine whether the user can permanently delete the form response.
     */
    public function forceDelete(User $user, FormResponse $formResponse): bool
    {
        return $this->userIsWorkspaceOwner($user, $formResponse->form->workspace_id);
    }

    /**
     * Determine whether the user can view sensitive data (IP, user agent).
     */
    public function viewSensitiveData(User $user, FormResponse $formResponse): bool
    {
        return $formResponse->form->created_by_id === $user->id ||
               $this->userIsWorkspaceAdmin($user, $formResponse->form->workspace_id);
    }

    /**
     * Determine whether the user can view user data (email, etc.).
     */
    public function viewUserData(User $user, FormResponse $formResponse): bool
    {
        return $this->viewSensitiveData($user, $formResponse);
    }

    /**
     * Check if user is workspace admin
     */
    private function userIsWorkspaceAdmin(User $user, int $workspaceId): bool
    {
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
        return $user->workspaces()
                    ->where('workspace_id', $workspaceId)
                    ->wherePivot('role', 'owner')
                    ->exists();
    }
}
