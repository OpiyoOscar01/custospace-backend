<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    /**
     * @var TaskRepositoryInterface
     */
    protected $taskRepository;

    /**
     * TaskService constructor.
     *
     * @param TaskRepositoryInterface $taskRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Get all tasks with pagination and filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllTasks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getAllTasks($filters, $perPage);
    }

    /**
     * Get a task by ID.
     *
     * @param int $taskId
     * @param array $relations
     * @return Task|null
     */
    public function getTaskById(int $taskId, array $relations = []): ?Task
    {
        return $this->taskRepository->findById($taskId, $relations);
    }

    /**
     * Create a new task.
     *
     * @param array $taskData
     * @return Task
     */
    public function createTask(array $taskData): Task
    {
        return $this->taskRepository->create($taskData);
    }

    /**
     * Update a task.
     *
     * @param Task $task
     * @param array $taskData
     * @return Task
     */
    public function updateTask(Task $task, array $taskData): Task
    {
        return $this->taskRepository->update($task, $taskData);
    }

    /**
     * Delete a task.
     *
     * @param Task $task
     * @return bool
     */
    public function deleteTask(Task $task): bool
    {
        return $this->taskRepository->delete($task);
    }

    /**
     * Get tasks by project.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksByProject($projectId, $filters, $perPage);
    }

    /**
     * Get tasks by workspace.
     *
     * @param int $workspaceId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByWorkspace(int $workspaceId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksByWorkspace($workspaceId, $filters, $perPage);
    }

    /**
     * Get tasks assigned to a user.
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByAssignee(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksByAssignee($userId, $filters, $perPage);
    }

    /**
     * Change the status of a task.
     *
     * @param Task $task
     * @param int $statusId
     * @return Task
     */
    public function changeStatus(Task $task, int $statusId): Task
    {
        return $this->taskRepository->updateStatus($task, $statusId);
    }

    /**
     * Assign a task to a user.
     *
     * @param Task $task
     * @param int|null $userId
     * @return Task
     */
    public function assignTask(Task $task, ?int $userId): Task
    {
        return $this->taskRepository->updateAssignee($task, $userId);
    }

    /**
     * Add task dependencies.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @param array $types
     * @return Task
     */
    public function addDependencies(Task $task, array $dependencyIds, array $types): Task
    {
        return $this->taskRepository->addDependencies($task, $dependencyIds, $types);
    }

    /**
     * Remove task dependencies.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @return Task
     */
    public function removeDependencies(Task $task, array $dependencyIds): Task
    {
        return $this->taskRepository->removeDependencies($task, $dependencyIds);
    }

    /**
     * Sync task milestones.
     *
     * @param Task $task
     * @param array $milestoneIds
     * @return Task
     */
    public function syncMilestones(Task $task, array $milestoneIds): Task
    {
        return $this->taskRepository->syncMilestones($task, $milestoneIds);
    }
}
