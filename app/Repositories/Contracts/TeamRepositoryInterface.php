<?php

namespace App\Repositories\Contracts;

use App\Models\Team;

interface TeamRepositoryInterface
{
    /**
     * Get teams by workspace with pagination.
     *
     * @param int $workspaceId
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTeamsByWorkspace(int $workspaceId, array $filters = []);

    /**
     * Get team by ID with relationships.
     *
     * @param int $id
     * @return Team
     */
    public function getTeamById(int $id);

    /**
     * Create a new team.
     *
     * @param array $data
     * @return Team
     */
    public function createTeam(array $data);

    /**
     * Update an existing team.
     *
     * @param Team $team
     * @param array $data
     * @return Team
     */
    public function updateTeam(Team $team, array $data);

    /**
     * Delete a team.
     *
     * @param Team $team
     * @return bool
     */
    public function deleteTeam(Team $team);
}
