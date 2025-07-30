<?php

namespace App\Repositories;

use App\Models\Goal;
use App\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Goal Repository Implementation
 * 
 * Handles all database operations for goals
 */
class GoalRepository implements GoalRepositoryInterface
{
    /**
     * Goal model instance
     *
     * @var Goal
     */
    protected Goal $model;

    /**
     * Constructor
     *
     * @param Goal $model
     */
    public function __construct(Goal $model)
    {
        $this->model = $model;
    }

    /**
     * Get all goals with optional filters
     *
     * @param array $filters
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply relationships
        if (!empty($with)) {
            $query->with($with);
        }

        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->forWorkspace($filters['workspace_id']);
        }

        if (isset($filters['team_id'])) {
            $query->forTeam($filters['team_id']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Paginate results
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Find goal by ID
     *
     * @param int $id
     * @param array $with
     * @return Goal|null
     */
    public function findById(int $id, array $with = []): ?Goal
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    /**
     * Create a new goal
     *
     * @param array $data
     * @return Goal
     */
    public function create(array $data): Goal
    {
        return $this->model->create($data);
    }

    /**
     * Update goal
     *
     * @param Goal $goal
     * @param array $data
     * @return bool
     */
    public function update(Goal $goal, array $data): bool
    {
        return $goal->update($data);
    }

    /**
     * Delete goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function delete(Goal $goal): bool
    {
        return $goal->delete();
    }

    /**
     * Get goals by workspace
     *
     * @param int $workspaceId
     * @param array $with
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId, array $with = []): Collection
    {
        $query = $this->model->forWorkspace($workspaceId);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get goals by team
     *
     * @param int $teamId
     * @param array $with
     * @return Collection
     */
    public function getByTeam(int $teamId, array $with = []): Collection
    {
        $query = $this->model->forTeam($teamId);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get goals by status
     *
     * @param string $status
     * @param array $with
     * @return Collection
     */
    public function getByStatus(string $status, array $with = []): Collection
    {
        $query = $this->model->byStatus($status);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Update goal progress
     *
     * @param Goal $goal
     * @param int $progress
     * @return bool
     */
    public function updateProgress(Goal $goal, int $progress): bool
    {
        return $goal->update(['progress' => $progress]);
    }

    /**
     * Activate goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function activate(Goal $goal): bool
    {
        return $goal->update(['status' => Goal::STATUS_ACTIVE]);
    }

    /**
     * Complete goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function complete(Goal $goal): bool
    {
        return $goal->update([
            'status' => Goal::STATUS_COMPLETED,
            'progress' => 100
        ]);
    }

    /**
     * Cancel goal
     *
     * @param Goal $goal
     * @return bool
     */
    public function cancel(Goal $goal): bool
    {
        return $goal->update(['status' => Goal::STATUS_CANCELLED]);
    }

    /**
     * Assign tasks to goal
     *
     * @param Goal $goal
     * @param array $taskIds
     * @return void
     */
    public function assignTasks(Goal $goal, array $taskIds): void
    {
        $goal->tasks()->sync($taskIds);
    }
}