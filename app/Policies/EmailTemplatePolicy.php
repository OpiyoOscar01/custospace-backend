<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Class EmailTemplatePolicy
 * 
 * Handles authorization for email template operations
 * 
 * @package App\Policies
 */
class EmailTemplatePolicy
{
    /**
     * Determine whether the user can view any email templates.
     */
    public function viewAny(User $user): bool
    {
        return true; // This should be refined based on workspace membership
    }

    /**
     * Determine whether the user can view the email template.
     */
    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        // Users can view templates if they:
        // 1. Have access to the workspace
        // 2. Template is system-wide (workspace_id is null)
        return $this->canAccessTemplate($user, $emailTemplate);
    }

    /**
     * Determine whether the user can create email templates.
     */
    public function create(User $user): bool
    {
        // Users with admin/editor role can create templates
        return true; // This should check workspace permissions
    }

    /**
     * Determine whether the user can update the email template.
     */
    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        // System templates can only be updated by super admins
        if ($emailTemplate->isSystemTemplate()) {
            return $this->isSuperAdmin($user);
        }

        return $this->canManageTemplate($user, $emailTemplate);
    }

    /**
     * Determine whether the user can delete the email template.
     */
    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        // System templates cannot be deleted
        if ($emailTemplate->isSystemTemplate()) {
            return false;
        }

        return $this->canManageTemplate($user, $emailTemplate);
    }

    /**
     * Determine whether the user can duplicate the email template.
     */
    public function duplicate(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->canAccessTemplate($user, $emailTemplate);
    }

    /**
     * Check if user can access the template
     * 
     * @param User $user
     * @param EmailTemplate $template
     * @return bool
     */
    protected function canAccessTemplate(User $user, EmailTemplate $template): bool
    {
        // System templates are accessible to all users
        if ($template->workspace_id === null) {
            return true;
        }

        // Check workspace membership (implement based on your workspace system)
        // return $user->belongsToWorkspace($template->workspace_id);
        
        return true; // Placeholder - implement based on your authorization logic
    }

    /**
     * Check if user can manage the template
     * 
     * @param User $user
     * @param EmailTemplate $template
     * @return bool
     */
    protected function canManageTemplate(User $user, EmailTemplate $template): bool
    {
        // Check if user has admin/editor permissions in the workspace
        // return $user->isWorkspaceAdminOrEditor($template->workspace_id);
        
        return true; // Placeholder - implement based on your authorization logic
    }

    /**
     * Check if user is super admin
     * 
     * @param User $user
     * @return bool
     */
    protected function isSuperAdmin(User $user): bool
    {
        // Implement based on your user role system
        // return $user->hasRole('super_admin');
        
        return false; // Placeholder
    }
}