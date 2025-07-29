<?php

namespace App\Repositories;

use App\Models\Milestone;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MilestoneRepository implements MilestoneRepositoryInterface
{
    /**
     * Get all milestones with optional filtering and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllMilestones(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Milestone::query();
        
        // Apply filters
        if (isset($filters['project_id'])) {
            $query->ofProject($filters['project_id']);
        }
        
        if (isset($filters['is_completed'])) {
            $query->completed($filters['is_completed']);
        }
        
        if (isset($filters['due_before'])) {
            $query->dueBefore($filters['due_before']);
        }
        
        if (isset($filters['upcoming'])) {
            $days = $filters['upcoming'] ?? 30;
            $query->upcoming($days);
        }
        
        if (isset($filters['with_relations']) && is_array($filters['with_relations'])) {
            $query->with($filters['with_relations']);
        }
        
        return $query->ordered()
                    ->paginate($perPage);
    }
    
    /**
     * Find a milestone by its ID.
     *
     * @param int $milestoneId
     * @param array $relations
     * @return Milestone|null
     */
    public function findById(int $milestoneId, array $relations = []): ?Milestone
    {
        return Milestone::with($relations)->find($milestoneId);
    }
    
    /**
     * Create a new milestone.
     *
     * @param array $milestoneData
     * @return Milestone
     */
    public function create(array $milestoneData): Milestone
    {
        // If no order is specified, make it the last in the list for this project
        if (!isset($milestoneData['order'])) {
            $maxOrder = Milestone::where('project_id', $milestoneData['project_id'])->max('order') ?? -1;
            $milestoneData['order'] = $maxOrder + 1;
        }
        
        // Extract non-milestone table data
        $taskIds = $milestoneData['task_ids'] ?? [];
        
        // Remove non-milestone table data
        $milestoneData = collect($milestoneData)->except(['task_ids'])->toArray();
        
        // Create the milestone
        $milestone = Milestone::create($milestoneData);
        
        // Associate tasks if provided
        if (!empty($taskIds)) {
            $milestone->tasks()->attach($taskIds);
        }
        
        return $milestone;
    }
    
    /**
     * Update a milestone.
     *
     * @param Milestone $milestone
     * @param array $milestoneData
     * @return Milestone
     */
    public function update(Milestone $milestone, array $milestoneData): Milestone
    {
        // Extract non-milestone table data
        $taskIds = $milestoneData['task_ids'] ?? null;
        
        // Remove non-milestone table data
        $milestoneData = collect($milestoneData)->except(['task_ids'])->toArray();
        
        // Update the milestone
        $milestone->update($milestoneData);
        
        // Sync tasks if provided
        if ($taskIds !== null) {
            $this->syncTasks($milestone, $taskIds);
        }
        
        return $milestone;
    }
    
    /**
     * Delete a milestone.
     *
     * @param Milestone $milestone
     * @return bool
     */
    public function delete(Milestone $milestone): bool
    {
        return $milestone->delete();
    }
    
    /**
     * Get milestones by project ID.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMilestonesByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['project_id'] = $projectId;
        return $this->getAllMilestones($filters, $perPage);
    }
    
    /**
     * Toggle the completion status of a milestone.
     *
     * @param Milestone $milestone
     * @param bool $isCompleted
     * @return Milestone
     */
    public function toggleCompletion(Milestone $milestone, bool $isCompleted): Milestone
    {
        $milestone->update(['is_completed' => $isCompleted]);
        return $milestone;
    }
    
    /**
     * Get upcoming milestones.
     *
     * @param int $days
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUpcomingMilestones(int $days = 30, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['upcoming'] = $days;
        return $this->getAllMilestones($filters, $perPage);
    }
    
    /**
     * Associate tasks with a milestone.
     *
     * @param Milestone $milestone
     * @param array $taskIds
     * @return Milestone
     */
    public function syncTasks(Milestone $milestone, array $taskIds): Milestone
    {
        $milestone->tasks()->sync($taskIds);
        return $milestone;
    }
    
    /**
     * Reorder milestones within a project.
     *
     * @param int $projectId
     * @param array $milestoneIds Ordered array of milestone IDs
     * @return bool
     */
    public function reorderMilestones(int $projectId, array $milestoneIds): bool
    {
        try {
            DB::beginTransaction();
            
            // Get all milestones for this project to ensure we only update those
            $projectMilestones = Milestone::where('project_id', $projectId)->get();
            $validMilestoneIds = $projectMilestones->pluck('id')->toArray();
            
            // Filter out any milestone IDs that don't belong to this project
            $validOrderedIds = array_filter($milestoneIds, function ($id) use ($validMilestoneIds) {
                return in_array($id, $validMilestoneIds);
            });
            
            // Update the order
            foreach ($validOrderedIds as $order => $id) {
                Milestone::where('id', $id)->update(['order' => $order]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}
