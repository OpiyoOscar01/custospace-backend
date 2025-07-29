<?php

namespace App\Repositories\Contracts;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface SubtaskRepositoryInterface
{
    /**
     * Get all subtasks for a task.
     *
     * @param int $taskId
     * @return Collection
     */
    public function getSubtasksByTaskId(int $taskId): Collection;
    
    /**
     * Find a subtask by its ID.
     *
     * @param int $subtaskId
     * @return Subtask|null
     */
    public function findById(int $subtaskId): ?Subtask;
    
    /**
     * Create a new subtask.
     *
     * @param array $subtaskData
     * @return Subtask
     */
    public function create(array $subtaskData): Subtask;
    
    /**
     * Update a subtask.
     *
     * @param Subtask $subtask
     * @param array $subtaskData
     * @return Subtask
     */
    public function update(Subtask $subtask, array $subtaskData): Subtask;
    
    /**
     * Delete a subtask.
     *
     * @param Subtask $subtask
     * @return bool
     */
    public function delete(Subtask $subtask): bool;
    
    /**
     * Toggle the completion status of a subtask.
     *
     * @param Subtask $subtask
     * @param bool $isCompleted
     * @return Subtask
     */
    public function toggleCompletion(Subtask $subtask, bool $isCompleted): Subtask;
    
    /**
     * Reorder subtasks.
     *
     * @param Task $task
     * @param array $subtaskIds Ordered array of subtask IDs
     * @return bool
     */
    public function reorderSubtasks(Task $task, array $subtaskIds): bool;
}
