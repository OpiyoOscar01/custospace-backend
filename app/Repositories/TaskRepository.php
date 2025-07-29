<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks with optional filtering and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllTasks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query();
        
        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->ofWorkspace($filters['workspace_id']);
        }
        
        if (isset($filters['project_id'])) {
            $query->ofProject($filters['project_id']);
        }
        
        if (isset($filters['status_id'])) {
            $query->ofStatus($filters['status_id']);
        }
        
        if (isset($filters['assignee_id'])) {
            $query->assignedTo($filters['assignee_id']);
        }
        
        if (isset($filters['priority'])) {
            $query->ofPriority($filters['priority']);
        }
        
        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }
        
        if (isset($filters['due_before'])) {
            $query->dueBy($filters['due_before']);
        }
        
        if (isset($filters['with_relations']) && is_array($filters['with_relations'])) {
            $query->with($filters['with_relations']);
        }
        
        return $query->orderBy('order', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }
    
    /**
     * Find a task by its ID.
     *
     * @param int $taskId
     * @param array $relations
     * @return Task|null
     */
    public function findById(int $taskId, array $relations = []): ?Task
    {
        return Task::with($relations)->find($taskId);
    }
    
    /**
     * Create a new task.
     *
     * @param array $taskData
     * @return Task
     */
    public function create(array $taskData): Task
    {
        // Extract non-task table data
        $milestoneIds = $taskData['milestone_ids'] ?? [];
        $dependencyIds = $taskData['dependency_ids'] ?? [];
        $dependencyTypes = $taskData['dependency_types'] ?? [];
        
        // Remove non-task table data from taskData
        $taskData = collect($taskData)
            ->except(['milestone_ids', 'dependency_ids', 'dependency_types'])
            ->toArray();
        
        $task = Task::create($taskData);
        
        // Associate milestones
        if (!empty($milestoneIds)) {
            $task->milestones()->attach($milestoneIds);
        }
        
        // Add dependencies
        if (!empty($dependencyIds) && !empty($dependencyTypes)) {
            $this->addDependencies($task, $dependencyIds, $dependencyTypes);
        }
        
        return $task;
    }
    
    /**
     * Update a task.
     *
     * @param Task $task
     * @param array $taskData
     * @return Task
     */
    public function update(Task $task, array $taskData): Task
    {
        // Extract non-task table data
        $milestoneIds = $taskData['milestone_ids'] ?? null;
        $dependencyIds = $taskData['dependency_ids'] ?? null;
        $dependencyTypes = $taskData['dependency_types'] ?? null;
        
        // Remove non-task table data from taskData
        $taskData = collect($taskData)
            ->except(['milestone_ids', 'dependency_ids', 'dependency_types'])
            ->toArray();
        
        $task->update($taskData);
        
        // Sync milestones if provided
        if ($milestoneIds !== null) {
            $this->syncMilestones($task, $milestoneIds);
        }
        
        // Sync dependencies if provided
        if ($dependencyIds !== null && $dependencyTypes !== null) {
            // First remove existing dependencies
            $task->dependencies()->detach();
            
            // Then add new ones
            if (!empty($dependencyIds)) {
                $this->addDependencies($task, $dependencyIds, $dependencyTypes);
            }
        }
        
        return $task;
    }
    
    /**
     * Delete a task.
     *
     * @param Task $task
     * @return bool
     */
    public function delete(Task $task): bool
    {
        return $task->delete();
    }
    
    /**
     * Get tasks by project ID.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['project_id'] = $projectId;
        return $this->getAllTasks($filters, $perPage);
    }
    
    /**
     * Get tasks by workspace ID.
     *
     * @param int $workspaceId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTasksByWorkspace(int $workspaceId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['workspace_id'] = $workspaceId;
        return $this->getAllTasks($filters, $perPage);
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
        $filters['assignee_id'] = $userId;
        return $this->getAllTasks($filters, $perPage);
    }
    
    /**
     * Add dependencies to a task.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @param array $types
     * @return Task
     */
    public function addDependencies(Task $task, array $dependencyIds, array $types): Task
    {
        $dependencies = [];
        
        foreach ($dependencyIds as $index => $dependencyId) {
            $type = $types[$index] ?? 'blocks'; // Default to 'blocks' if type not provided
            $dependencies[$dependencyId] = ['type' => $type];
        }
        
        $task->dependencies()->attach($dependencies);
        
        return $task;
    }
    
    /**
     * Remove dependencies from a task.
     *
     * @param Task $task
     * @param array $dependencyIds
     * @return Task
     */
    public function removeDependencies(Task $task, array $dependencyIds): Task
    {
        $task->dependencies()->detach($dependencyIds);
        
        return $task;
    }
    
    /**
     * Associate milestones with a task.
     *
     * @param Task $task
     * @param array $milestoneIds
     * @return Task
     */
    public function syncMilestones(Task $task, array $milestoneIds): Task
    {
        $task->milestones()->sync($milestoneIds);
        
        return $task;
    }
    
    /**
     * Update task status.
     *
     * @param Task $task
     * @param int $statusId
     * @return Task
     */
    public function updateStatus(Task $task, int $statusId): Task
    {
        $task->update(['status_id' => $statusId]);
        
        return $task;
    }
    
    /**
     * Update task assignee.
     *
     * @param Task $task
     * @param int|null $assigneeId
     * @return Task
     */
    public function updateAssignee(Task $task, ?int $assigneeId): Task
    {
        $task->update(['assignee_id' => $assigneeId]);
        
        return $task;
    }
}
