<?php

namespace App\Repositories\Contracts;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Goal Repository Interface
 * 
 * Defines the contract for goal data access operations
 */
interface GoalRepositoryInterface
{
    /**
     * Get all goals with optional filters
     *
     * @param array $filters
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], array $with = []): LengthAwarePaginator;

    /**
     * Find goal by ID
     *
     * @param int $id
     * @param array $with
     * @return Goal|null
     */
    public function findById(int $id, array $with = []): ?Goal;

    /**
     * Create a new goal
     *
     * @param array $data
     * @return Goal
     */
    public function create(array $data): Goal;

    /**
     * Update goal
     *
     * @param Goal $goal
     * @param array $data
     * @return bool
     */
    public function update(Goal $goal, array $data): bool;

    /**
     * Delete goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function delete(Goal $goal): bool;

    /**
     * Get goals by workspace
     *
     * @param int $workspaceId
     * @param array $with
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId, array $with = []): Collection;

    /**
     * Get goals by team
     *
     * @param int $teamId
     * @param array $with
     * @return Collection
     */
    public function getByTeam(int $teamId, array $with = []): Collection;

    /**
     * Get goals by status
     *
     * @param string $status
     * @param array $with
     * @return Collection
     */
    public function getByStatus(string $status, array $with = []): Collection;

    /**
     * Update goal progress
     *
     * @param Goal $goal
     * @param int $progress
     * @return bool
     */
    public function updateProgress(Goal $goal, int $progress): bool;

    /**
     * Activate goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function activate(Goal $goal): bool;

    /**
     * Complete goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function complete(Goal $goal): bool;

    /**
     * Cancel goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function cancel(Goal $goal): bool;

    /**
     * Assign tasks to goal
     *
     * @param Goal $goal
     * @param array $taskIds
     * @return void
     */
    public function assignTasks(Goal $goal, array $taskIds): void;
}