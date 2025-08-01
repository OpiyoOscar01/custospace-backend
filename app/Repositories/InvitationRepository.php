<?php

namespace App\Repositories;

use App\Models\Invitation;
use App\Models\User;
use App\Repositories\Contracts\InvitationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class InvitationRepository
 * 
 * Repository implementation for invitation operations
 * 
 * @package App\Repositories
 */
class InvitationRepository implements InvitationRepositoryInterface
{
    /**
     * Get all invitations with filters and pagination
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = Invitation::query()
            ->with(['workspace', 'team', 'invitedBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new invitation
     * 
     * @param array $data
     * @return Invitation
     */
    public function create(array $data): Invitation
    {
        return Invitation::create($data);
    }

    /**
     * Update an invitation
     * 
     * @param Invitation $invitation
     * @param array $data
     * @return Invitation
     */
    public function update(Invitation $invitation, array $data): Invitation
    {
        $invitation->update($data);
        return $invitation->fresh();
    }

    /**
     * Delete an invitation
     * 
     * @param Invitation $invitation
     * @return bool
     */
    public function delete(Invitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * Find invitation by token
     * 
     * @param string $token
     * @return Invitation|null
     */
    public function findByToken(string $token): ?Invitation
    {
        return Invitation::where('token', $token)->first();
    }

    /**
     * Bulk delete invitations
     * 
     * @param array $invitationIds
     * @param User $user
     * @return int
     */
    public function bulkDelete(array $invitationIds, User $user): int
    {
        // Only allow deletion of invitations the user has permission to delete
        return Invitation::whereIn('id', $invitationIds)
            ->where(function ($query) use ($user) {
                // Add authorization logic here based on your business rules
                $query->where('invited_by_id', $user->id);
                // Or check workspace permissions, etc.
            })
            ->delete();
    }

    /**
     * Expire old invitations
     * 
     * @return int
     */
    public function expireOldInvitations(): int
    {
        return Invitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Get invitations by workspace
     * 
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return Invitation::byWorkspace($workspaceId)
            ->with(['team', 'invitedBy'])
            ->get();
    }

    /**
     * Apply filters to the query
     * 
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['workspace_id'])) {
            $query->byWorkspace($filters['workspace_id']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
    }
}