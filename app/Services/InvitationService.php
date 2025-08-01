<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use App\Repositories\Contracts\InvitationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Class InvitationService
 * 
 * Handles business logic for invitation operations
 * 
 * @package App\Services
 */
class InvitationService
{
    /**
     * InvitationService constructor.
     */
    public function __construct(
        protected InvitationRepositoryInterface $invitationRepository
    ) {}

    /**
     * Get all invitations with filters and pagination
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllInvitations(array $filters = []): LengthAwarePaginator
    {
        return $this->invitationRepository->getAllWithFilters($filters);
    }

    /**
     * Create a new invitation
     * 
     * @param array $data
     * @param User $inviter
     * @return Invitation
     */
    public function createInvitation(array $data, User $inviter): Invitation
    {
        return DB::transaction(function () use ($data, $inviter) {
            // Set the inviter
            $data['invited_by_id'] = $inviter->id;
            
            // Generate unique token
            $data['token'] = $this->generateUniqueToken();
            
            // Set default expiration if not provided
            if (!isset($data['expires_at'])) {
                $data['expires_at'] = now()->addDays(7);
            }

            $invitation = $this->invitationRepository->create($data);

            // Send invitation email
            $this->sendInvitationEmail($invitation);

            return $invitation->load(['workspace', 'team', 'invitedBy']);
        });
    }

    /**
     * Update an existing invitation
     * 
     * @param Invitation $invitation
     * @param array $data
     * @return Invitation
     */
    public function updateInvitation(Invitation $invitation, array $data): Invitation
    {
        return DB::transaction(function () use ($invitation, $data) {
            $updatedInvitation = $this->invitationRepository->update($invitation, $data);
            
            return $updatedInvitation->load(['workspace', 'team', 'invitedBy']);
        });
    }

    /**
     * Delete an invitation
     * 
     * @param Invitation $invitation
     * @return bool
     */
    public function deleteInvitation(Invitation $invitation): bool
    {
        return $this->invitationRepository->delete($invitation);
    }

    /**
     * Accept an invitation
     * 
     * @param Invitation $invitation
     * @param User $user
     * @return array
     */
    public function acceptInvitation(Invitation $invitation, User $user): array
    {
        if (!$invitation->canBeAccepted()) {
            throw new \InvalidArgumentException('Invitation cannot be accepted');
        }

        return DB::transaction(function () use ($invitation, $user) {
            // Update invitation status
            $invitation->update(['status' => 'accepted']);

            // Add user to workspace/team
            $this->addUserToWorkspace($user, $invitation);

            return [
                'invitation' => $invitation,
                'workspace_membership' => true
            ];
        });
    }

    /**
     * Decline an invitation
     * 
     * @param Invitation $invitation
     * @return Invitation
     */
    public function declineInvitation(Invitation $invitation): Invitation
    {
        return $this->invitationRepository->update($invitation, ['status' => 'declined']);
    }

    /**
     * Resend an invitation
     * 
     * @param Invitation $invitation
     * @return Invitation
     */
    public function resendInvitation(Invitation $invitation): Invitation
    {
        if (!$invitation->isPending()) {
            throw new \InvalidArgumentException('Only pending invitations can be resent');
        }

        return DB::transaction(function () use ($invitation) {
            // Update expiration and regenerate token
            $invitation->update([
                'token' => $this->generateUniqueToken(),
                'expires_at' => now()->addDays(7)
            ]);

            // Resend email
            $this->sendInvitationEmail($invitation);

            return $invitation;
        });
    }

    /**
     * Bulk delete invitations
     * 
     * @param array $invitationIds
     * @param User $user
     * @return int
     */
    public function bulkDeleteInvitations(array $invitationIds, User $user): int
    {
        return $this->invitationRepository->bulkDelete($invitationIds, $user);
    }

    /**
     * Expire old invitations
     * 
     * @return int
     */
    public function expireOldInvitations(): int
    {
        return $this->invitationRepository->expireOldInvitations();
    }

    /**
     * Generate unique invitation token
     * 
     * @return string
     */
    protected function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while ($this->invitationRepository->findByToken($token));

        return $token;
    }

    /**
     * Send invitation email
     * 
     * @param Invitation $invitation
     * @return void
     */
    protected function sendInvitationEmail(Invitation $invitation): void
    {
        // Implementation would depend on your mail system
        // Mail::to($invitation->email)->send(new InvitationMail($invitation));
    }

    /**
     * Add user to workspace based on invitation
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return void
     */
    protected function addUserToWorkspace(User $user, Invitation $invitation): void
    {
        // Implementation would depend on your workspace membership system
        // This is a placeholder for the actual implementation
    }
}