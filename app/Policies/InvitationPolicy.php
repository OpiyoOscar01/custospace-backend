<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Class InvitationPolicy
 * 
 * Handles authorization for invitation operations
 * 
 * @package App\Policies
 */
class InvitationPolicy
{
    /**
     * Determine whether the user can view any invitations.
     */
    public function viewAny(User $user): bool
    {
        // Users can view invitations for workspaces they belong to
        return true; // This should be refined based on workspace membership
    }

    /**
     * Determine whether the user can view the invitation.
     */
    public function view(User $user, Invitation $invitation): bool
    {
        // Users can view invitations if they:
        // 1. Sent the invitation
        // 2. Are workspace admin/owner
        // 3. Are the invited user (by email)
        return $this->canManageInvitation($user, $invitation) ||
               $user->email === $invitation->email;
    }

    /**
     * Determine whether the user can create invitations.
     */
    public function create(User $user): bool
    {
        // Users with admin/owner role in any workspace can create invitations
        return true; // This should check workspace permissions
    }

    /**
     * Determine whether the user can update the invitation.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return $this->canManageInvitation($user, $invitation);
    }

    /**
     * Determine whether the user can delete the invitation.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $this->canManageInvitation($user, $invitation);
    }

    /**
     * Determine whether the user can accept the invitation.
     */
    public function accept(User $user, Invitation $invitation): bool
    {
        return $user->email === $invitation->email && 
               $invitation->canBeAccepted();
    }

    /**
     * Determine whether the user can decline the invitation.
     */
    public function decline(User $user, Invitation $invitation): bool
    {
        return $user->email === $invitation->email && 
               $invitation->isPending();
    }

    /**
     * Determine whether the user can resend the invitation.
     */
    public function resend(User $user, Invitation $invitation): bool
    {
        return $this->canManageInvitation($user, $invitation) &&
               $invitation->isPending();
    }

    /**
     * Determine whether the user can view the invitation token.
     */
    public function viewToken(User $user, Invitation $invitation): bool
    {
        return $this->canManageInvitation($user, $invitation);
    }

    /**
     * Check if user can manage the invitation
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return bool
     */
    protected function canManageInvitation(User $user, Invitation $invitation): bool
    {
        // User sent the invitation
        if ($invitation->invited_by_id === $user->id) {
            return true;
        }

        // User is workspace admin/owner (implement based on your workspace system)
        // return $user->isWorkspaceAdmin($invitation->workspace_id);
        
        return false; // Placeholder - implement based on your authorization logic
    }
}