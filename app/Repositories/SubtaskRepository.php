<?php

namespace App\Repositories;

use App\Models\Subtask;
use App\Models\Task;
use App\Repositories\Contracts\SubtaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SubtaskRepository implements SubtaskRepositoryInterface
{
    /**
     * Get all subtasks for a task.
     *
     * @param int $taskId
     * @return Collection
     */
    public function getSubtasksByTaskId(int $taskId): Collection
    {
        return Subtask::where('task_id', $taskId)
            ->ordered()
            ->get();
    }
    
    /**
     * Find a subtask by its ID.
     *
     * @param int $subtaskId
     * @return Subtask|null
     */
    public function findById(int $subtaskId): ?Subtask
    {
        return Subtask::find($subtaskId);
    }
    
    /**
     * Create a new subtask.
     *
     * @param array $subtaskData
     * @return Subtask
     */
    public function create(array $subtaskData): Subtask
    {
        // If no order is specified, make it the last in the list
        if (!isset($subtaskData['order'])) {
            $maxOrder = Subtask::where('task_id', $subtaskData['task_id'])->max('order') ?? -1;
            $subtaskData['order'] = $maxOrder + 1;
        }
        
        return Subtask::create($subtaskData);
    }
    
    /**
     * Update a subtask.
     *
     * @param Subtask $subtask
     * @param array $subtaskData
     * @return Subtask
     */
    public function update(Subtask $subtask, array $subtaskData): Subtask
    {
        $subtask->update($subtaskData);
        return $subtask;
    }
    
    /**
     * Delete a subtask.
     *
     * @param Subtask $subtask
     * @return bool
     */
    public function delete(Subtask $subtask): bool
    {
        return $subtask->delete();
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
        $subtask->update(['is_completed' => $isCompleted]);
        return $subtask;
    }
    
    /**
     * Reorder subtasks.
     *
     * @param Task $task
     * @param array $subtaskIds Ordered array of subtask IDs
     * @return bool
     */
    public function reorderSubtasks(Task $task, array $subtaskIds): bool
    {
        try {
            DB::beginTransaction();
            
            // Get all subtasks for this task to ensure we only update the ones that belong to it
            $subtasks = $task->subtasks;
            $validSubtaskIds = $subtasks->pluck('id')->toArray();
            
            // Filter out any subtask IDs that don't belong to this task
            $validOrderedIds = array_filter($subtaskIds, function ($id) use ($validSubtaskIds) {
                return in_array($id, $validSubtaskIds);
            });
            
            // Update the order
            foreach ($validOrderedIds as $order => $id) {
                Subtask::where('id', $id)->update(['order' => $order]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}
