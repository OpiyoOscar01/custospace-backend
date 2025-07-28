<?php

namespace App\Repositories;

use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TeamRepository implements TeamRepositoryInterface
{
    /**
     * Get teams by workspace with pagination and filtering.
     *
     * @param int $workspaceId
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTeamsByWorkspace(int $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = Team::where('workspace_id', $workspaceId);
        
        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('slug', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply sorting
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        // Load relationships if requested
        if (isset($filters['with_users']) && $filters['with_users']) {
            $query->with('users');
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Get team by ID with relationships.
     *
     * @param int $id
     * @return Team
     */
    public function getTeamById(int $id): Team
    {
        return Team::with(['workspace', 'users'])->findOrFail($id);
    }

    /**
     * Create a new team.
     *
     * @param array $data
     * @return Team
     */
    public function createTeam(array $data): Team
    {
        return Team::create($data);
    }

    /**
     * Update an existing team.
     *
     * @param Team $team
     * @param array $data
     * @return Team
     */
    public function updateTeam(Team $team, array $data): Team
    {
        $team->update($data);
        return $team->fresh();
    }

    /**
     * Delete a team.
     *
     * @param Team $team
     * @return bool
     */
    public function deleteTeam(Team $team): bool
    {
        return $team->delete();
    }
}
