<?php

namespace App\Services;

use App\Models\Subtask;
use App\Models\Task;
use App\Repositories\Contracts\SubtaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubtaskService
{
    /**
     * @var SubtaskRepositoryInterface
     */
    protected $subtaskRepository;

    /**
     * SubtaskService constructor.
     *
     * @param SubtaskRepositoryInterface $subtaskRepository
     */
    public function __construct(SubtaskRepositoryInterface $subtaskRepository)
    {
        $this->subtaskRepository = $subtaskRepository;
    }

    /**
     * Get all subtasks for a task.
     *
     * @param int $taskId
     * @return Collection
     */
    public function getSubtasksByTaskId(int $taskId): Collection
    {
        return $this->subtaskRepository->getSubtasksByTaskId($taskId);
    }

    /**
     * Get a subtask by ID.
     *
     * @param int $subtaskId
     * @return Subtask|null
     */
    public function getSubtaskById(int $subtaskId): ?Subtask
    {
        return $this->subtaskRepository->findById($subtaskId);
    }

    /**
     * Create a new subtask.
     *
     * @param array $subtaskData
     * @return Subtask
     */
    public function createSubtask(array $subtaskData): Subtask
    {
        return $this->subtaskRepository->create($subtaskData);
    }

    /**
     * Update a subtask.
     *
     * @param Subtask $subtask
     * @param array $subtaskData
     * @return Subtask
     */
    public function updateSubtask(Subtask $subtask, array $subtaskData): Subtask
    {
        return $this->subtaskRepository->update($subtask, $subtaskData);
    }

    /**
     * Delete a subtask.
     *
     * @param Subtask $subtask
     * @return bool
     */
    public function deleteSubtask(Subtask $subtask): bool
    {
        return $this->subtaskRepository->delete($subtask);
    }

    /**
     * Toggle the completion status of a subtask.
     *
     * @param Subtask $subtask
     * @param bool $isCompleted
     * @return Subtask
     */
    public function toggleCompletion(Subtask $subtask, bool $isCompleted): Subtask
    {
        return $this->subtaskRepository->toggleCompletion($subtask, $isCompleted);
    }

    /**
     * Reorder subtasks.
     *
     * @param Task $task
     * @param array $subtaskIds
     * @return bool
     */
    public function reorderSubtasks(Task $task, array $subtaskIds): bool
    {
        return $this->subtaskRepository->reorderSubtasks($task, $subtaskIds);
    }
}
