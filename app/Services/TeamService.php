<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Workspace;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeamService
{
    protected $teamRepository;

    /**
     * Create a new service instance.
     */
    public function __construct(TeamRepositoryInterface $teamRepository)
    {
        $this->teamRepository = $teamRepository;
    }

    /**
     * Get teams by workspace with optional filtering.
     *
     * @param Workspace $workspace
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTeamsByWorkspace(Workspace $workspace, array $filters = [])
    {
        return $this->teamRepository->getTeamsByWorkspace($workspace->id, $filters);
    }

    /**
     * Get team by ID with relationships.
     *
     * @param int $id
     * @return Team
     */
    public function getTeamById(int $id)
    {
        return $this->teamRepository->getTeamById($id);
    }

    /**
     * Create a new team.
     *
     * @param array $data
     * @return Team
     */
    public function createTeam(array $data)
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $team = $this->teamRepository->createTeam($data);

        // Assign current user as owner
        if (Auth::check()) {
            $team->users()->attach(Auth::id(), ['role' => 'owner']);
        }

        return $team;
    }

    /**
     * Update an existing team.
     *
     * @param Team $team
     * @param array $data
     * @return Team
     */
    public function updateTeam(Team $team, array $data)
    {
        return $this->teamRepository->updateTeam($team, $data);
    }

    /**
     * Delete a team.
     *
     * @param Team $team
     * @return bool
     */
    public function deleteTeam(Team $team)
    {
        return $this->teamRepository->deleteTeam($team);
    }

    /**
     * Activate a team.
     *
     * @param Team $team
     * @return Team
     */
    public function activateTeam(Team $team)
    {
        return $this->teamRepository->updateTeam($team, ['is_active' => true]);
    }

    /**
     * Deactivate a team.
     *
     * @param Team $team
     * @return Team
     */
    public function deactivateTeam(Team $team)
    {
        return $this->teamRepository->updateTeam($team, ['is_active' => false]);
    }

    /**
     * Assign a user to the team.
     *
     * @param Team $team
     * @param int $userId
     * @param string $role
     * @return Team
     */
    public function assignUser(Team $team, int $userId, string $role)
    {
        // Check if user belongs to the workspace
        $workspace = $team->workspace;
        if (!$workspace->users()->where('user_id', $userId)->exists()) {
            // Add user to workspace as member first
            $workspace->users()->syncWithoutDetaching([
                $userId => [
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);
        }

        // Add user to team
        $team->users()->syncWithoutDetaching([
            $userId => [
                'role' => $role,
                'joined_at' => now(),
            ]
        ]);

        return $team->fresh(['users']);
    }
}
