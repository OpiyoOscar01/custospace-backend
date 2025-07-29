<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks with optional filtering and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllTasks(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Find a task by its ID.
     *
     * @param int $taskId
     * @param array $relations
     * @return Task|null
     */
    public function findById(int $taskId, array $relations = []): ?Task;
    
    /**
     * Create a new task.
     *
     * @param array $taskData
     * @return Task
     */
    public function create(array $taskData): Task;
    
    /**
     * Update a task.
     *
     * @param Task $task
     * @param array $taskData
     * @return Task
     */
    public function update(Task $task, array $taskData): Task;
    
    /**
     * Delete a task.
     *
     * @param Task $task
     * @return bool
     */
    public function delete(Task $task): bool;
    
    /**
     * Get tasks by project ID.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Get tasks by workspace ID.
     *
     * @param int $workspaceId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByWorkspace(int $workspaceId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Get tasks assigned to a user.
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByAssignee(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Add dependencies to a task.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @param array $types
     * @return Task
     */
    public function addDependencies(Task $task, array $dependencyIds, array $types): Task;
    
    /**
     * Remove dependencies from a task.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @return Task
     */
    public function removeDependencies(Task $task, array $dependencyIds): Task;
    
    /**
     * Associate milestones with a task.
     *
     * @param Task $task
     * @param array $milestoneIds
     * @return Task
     */
    public function syncMilestones(Task $task, array $milestoneIds): Task;
    
    /**
     * Update task status.
     *
     * @param Task $task
     * @param int $statusId
     * @return Task
     */
    public function updateStatus(Task $task, int $statusId): Task;
    
    /**
     * Update task assignee.
     *
     * @param Task $task
     * @param int|null $assigneeId
     * @return Task
     */
    public function updateAssignee(Task $task, ?int $assigneeId): Task;
}
