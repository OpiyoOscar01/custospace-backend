<?php

namespace App\Repositories\Contracts;

use App\Models\Milestone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface MilestoneRepositoryInterface
{
    /**
     * Get all milestones with optional filtering and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllMilestones(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Find a milestone by its ID.
     *
     * @param int $milestoneId
     * @param array $relations
     * @return Milestone|null
     */
    public function findById(int $milestoneId, array $relations = []): ?Milestone;
    
    /**
     * Create a new milestone.
     *
     * @param array $milestoneData
     * @return Milestone
     */
    public function create(array $milestoneData): Milestone;
    
    /**
     * Update a milestone.
     *
     * @param Milestone $milestone
     * @param array $milestoneData
     * @return Milestone
     */
    public function update(Milestone $milestone, array $milestoneData): Milestone;
    
    /**
     * Delete a milestone.
     *
     * @param Milestone $milestone
     * @return bool
     */
    public function delete(Milestone $milestone): bool;
    
    /**
     * Get milestones by project ID.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMilestonesByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Toggle the completion status of a milestone.
     *
     * @param Milestone $milestone
     * @param bool $isCompleted
     * @return Milestone
     */
    public function toggleCompletion(Milestone $milestone, bool $isCompleted): Milestone;
    
    /**
     * Get upcoming milestones.
     *
     * @param int $days
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUpcomingMilestones(int $days = 30, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Associate tasks with a milestone.
     *
     * @param Milestone $milestone
     * @param array $taskIds
     * @return Milestone
     */
    public function syncTasks(Milestone $milestone, array $taskIds): Milestone;
    
    /**
     * Reorder milestones within a project.
     *
     * @param int $projectId
     * @param array $milestoneIds Ordered array of milestone IDs
     * @return bool
     */
    public function reorderMilestones(int $projectId, array $milestoneIds): bool;
}
