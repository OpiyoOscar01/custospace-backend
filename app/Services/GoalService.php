<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use App\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Goal Service
 * 
 * Handles all business logic for goal operations
 */
class GoalService
{
    /**
     * Goal repository instance
     *
     * @var GoalRepositoryInterface
     */
    protected GoalRepositoryInterface $goalRepository;

    /**
     * Constructor
     *
     * @param GoalRepositoryInterface $goalRepository
     */
    public function __construct(GoalRepositoryInterface $goalRepository)
    {
        $this->goalRepository = $goalRepository;
    }

    /**
     * Get paginated goals with filters
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginatedGoals(array $filters = []): LengthAwarePaginator
    {
        $with = ['workspace', 'team', 'owner', 'tasks'];
        return $this->goalRepository->getAllPaginated($filters, $with);
    }

    /**
     * Find goal by ID
     *
     * @param int $id
     * @return Goal|null
     */
    public function findGoal(int $id): ?Goal
    {
        $with = ['workspace', 'team', 'owner', 'tasks'];
        return $this->goalRepository->findById($id, $with);
    }

    /**
     * Create a new goal
     *
     * @param array $data
     * @return Goal
     * @throws Exception
     */
    public function createGoal(array $data): Goal
    {
        try {
            DB::beginTransaction();

            $goal = $this->goalRepository->create($data);

            // If tasks are provided, assign them to the goal
            if (isset($data['task_ids']) && is_array($data['task_ids'])) {
                $this->goalRepository->assignTasks($goal, $data['task_ids']);
            }

            DB::commit();

            return $this->findGoal($goal->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to create goal: ' . $e->getMessage());
        }
    }

    /**
     * Update goal
     *
     * @param Goal $goal
     * @param array $data
     * @return Goal
     * @throws Exception
     */
    public function updateGoal(Goal $goal, array $data): Goal
    {
        try {
            DB::beginTransaction();

            $this->goalRepository->update($goal, $data);

            // If tasks are provided, update the assignment
            if (isset($data['task_ids']) && is_array($data['task_ids'])) {
                $this->goalRepository->assignTasks($goal, $data['task_ids']);
            }

            DB::commit();

            return $this->findGoal($goal->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to update goal: ' . $e->getMessage());
        }
    }

    /**
     * Delete goal
     *
     * @param Goal $goal
     * @return bool
     * @throws Exception
     */
    public function deleteGoal(Goal $goal): bool
    {
        try {
            return $this->goalRepository->delete($goal);
        } catch (Exception $e) {
            throw new Exception('Failed to delete goal: ' . $e->getMessage());
        }
    }

    /**
     * Activate goal
     *
     * @param Goal $goal
     * @return Goal
     * @throws Exception
     */
    public function activateGoal(Goal $goal): Goal
    {
        if ($goal->status === Goal::STATUS_ACTIVE) {
            throw new Exception('Goal is already active.');
        }

        if ($goal->status === Goal::STATUS_COMPLETED) {
            throw new Exception('Cannot activate a completed goal.');
        }

        if ($goal->status === Goal::STATUS_CANCELLED) {
            throw new Exception('Cannot activate a cancelled goal.');
        }

        $this->goalRepository->activate($goal);

        return $this->findGoal($goal->id);
    }

    /**
     * Complete goal
     *
     * @param Goal $goal
     * @return Goal
     * @throws Exception
     */
    public function completeGoal(Goal $goal): Goal
    {
        if ($goal->status === Goal::STATUS_COMPLETED) {
            throw new Exception('Goal is already completed.');
        }

        if ($goal->status === Goal::STATUS_CANCELLED) {
            throw new Exception('Cannot complete a cancelled goal.');
        }

        $this->goalRepository->complete($goal);

        return $this->findGoal($goal->id);
    }

    /**
     * Cancel goal
     *
     * @param Goal $goal
     * @return Goal
     * @throws Exception
     */
    public function cancelGoal(Goal $goal): Goal
    {
        if ($goal->status === Goal::STATUS_CANCELLED) {
            throw new Exception('Goal is already cancelled.');
        }

        if ($goal->status === Goal::STATUS_COMPLETED) {
            throw new Exception('Cannot cancel a completed goal.');
        }

        $this->goalRepository->cancel($goal);

        return $this->findGoal($goal->id);
    }

    /**
     * Update goal progress
     *
     * @param Goal $goal
     * @param int $progress
     * @return Goal
     * @throws Exception
     */
    public function updateProgress(Goal $goal, int $progress): Goal
    {
        if ($progress < 0 || $progress > 100) {
            throw new Exception('Progress must be between 0 and 100.');
        }

        if ($goal->status === Goal::STATUS_COMPLETED) {
            throw new Exception('Cannot update progress of a completed goal.');
        }

        if ($goal->status === Goal::STATUS_CANCELLED) {
            throw new Exception('Cannot update progress of a cancelled goal.');
        }

        $this->goalRepository->updateProgress($goal, $progress);

        // Auto-complete goal if progress reaches 100%
        if ($progress === 100 && $goal->status !== Goal::STATUS_COMPLETED) {
            $this->goalRepository->complete($goal);
        }

        return $this->findGoal($goal->id);
    }

    /**
     * Assign user to goal
     *
     * @param Goal $goal
     * @param User $user
     * @return Goal
     * @throws Exception
     */
    public function assignUser(Goal $goal, User $user): Goal
    {
        try {
            $this->goalRepository->update($goal, ['owner_id' => $user->id]);
            return $this->findGoal($goal->id);
        } catch (Exception $e) {
            throw new Exception('Failed to assign user to goal: ' . $e->getMessage());
        }
    }

    /**
     * Assign tasks to goal
     *
     * @param Goal $goal
     * @param array $taskIds
     * @return Goal
     * @throws Exception
     */
    public function assignTasks(Goal $goal, array $taskIds): Goal
    {
        try {
            $this->goalRepository->assignTasks($goal, $taskIds);
            return $this->findGoal($goal->id);
        } catch (Exception $e) {
            throw new Exception('Failed to assign tasks to goal: ' . $e->getMessage());
        }
    }

    /**
     * Get goals by workspace
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getGoalsByWorkspace(int $workspaceId): Collection
    {
        $with = ['team', 'owner', 'tasks'];
        return $this->goalRepository->getByWorkspace($workspaceId, $with);
    }

    /**
     * Get goals by team
     *
     * @param int $teamId
     * @return Collection
     */
    public function getGoalsByTeam(int $teamId): Collection
    {
        $with = ['workspace', 'owner', 'tasks'];
        return $this->goalRepository->getByTeam($teamId, $with);
    }

    /**
     * Get goals by status
     *
     * @param string $status
     * @return Collection
     */
    public function getGoalsByStatus(string $status): Collection
    {
        $with = ['workspace', 'team', 'owner', 'tasks'];
        return $this->goalRepository->getByStatus($status, $with);
    }
}