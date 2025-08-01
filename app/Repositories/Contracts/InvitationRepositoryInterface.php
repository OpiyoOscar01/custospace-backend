<?php

namespace App\Repositories\Contracts;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface InvitationRepositoryInterface
 * 
 * Contract for invitation repository operations
 * 
 * @package App\Repositories\Contracts
 */
interface InvitationRepositoryInterface
{
    /**
     * Get all invitations with filters and pagination
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator;

    /**
     * Create a new invitation
     * 
     * @param array $data
     * @return Invitation
     */
    public function create(array $data): Invitation;

    /**
     * Update an invitation
     * 
     * @param Invitation $invitation
     * @param array $data
     * @return Invitation
     */
    public function update(Invitation $invitation, array $data): Invitation;

    /**
     * Delete an invitation
     * 
     * @param Invitation $invitation
     * @return bool
     */
    public function delete(Invitation $invitation): bool;

    /**
     * Find invitation by token
     * 
     * @param string $token
     * @return Invitation|null
     */
    public function findByToken(string $token): ?Invitation;

    /**
     * Bulk delete invitations
     * 
     * @param array $invitationIds
     * @param User $user
     * @return int
     */
    public function bulkDelete(array $invitationIds, User $user): int;

    /**
     * Expire old invitations
     * 
     * @return int
     */
    public function expireOldInvitations(): int;

    /**
     * Get invitations by workspace
     * 
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection;
}