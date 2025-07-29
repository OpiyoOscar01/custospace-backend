<?php

namespace App\Services;

use App\Models\Milestone;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MilestoneService
{
    /**
     * @var MilestoneRepositoryInterface
     */
    protected $milestoneRepository;

    /**
     * MilestoneService constructor.
     *
     * @param MilestoneRepositoryInterface $milestoneRepository
     */
    public function __construct(MilestoneRepositoryInterface $milestoneRepository)
    {
        $this->milestoneRepository = $milestoneRepository;
    }

    /**
     * Get all milestones with pagination and filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllMilestones(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->milestoneRepository->getAllMilestones($filters, $perPage);
    }

    /**
     * Get a milestone by ID.
     *
     * @param int $milestoneId
     * @param array $relations
     * @return Milestone|null
     */
    public function getMilestoneById(int $milestoneId, array $relations = []): ?Milestone
    {
        return $this->milestoneRepository->findById($milestoneId, $relations);
    }

    /**
     * Create a new milestone.
     *
     * @param array $milestoneData
     * @return Milestone
     */
    public function createMilestone(array $milestoneData): Milestone
    {
        return $this->milestoneRepository->create($milestoneData);
    }

    /**
     * Update a milestone.
     *
     * @param Milestone $milestone
     * @param array $milestoneData
     * @return Milestone
     */
    public function updateMilestone(Milestone $milestone, array $milestoneData): Milestone
    {
        return $this->milestoneRepository->update($milestone, $milestoneData);
    }

    /**
     * Delete a milestone.
     *
     * @param Milestone $milestone
     * @return bool
     */
    public function deleteMilestone(Milestone $milestone): bool
    {
        return $this->milestoneRepository->delete($milestone);
    }

    /**
     * Get milestones by project.
     *
     * @param int $projectId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMilestonesByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->milestoneRepository->getMilestonesByProject($projectId, $filters, $perPage);
    }

    /**
     * Toggle milestone completion status.
     *
     * @param Milestone $milestone
     * @param bool $isCompleted
     * @return Milestone
     */
    public function toggleCompletion(Milestone $milestone, bool $isCompleted): Milestone
    {
        return $this->milestoneRepository->toggleCompletion($milestone, $isCompleted);
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
        return $this->milestoneRepository->getUpcomingMilestones($days, $filters, $perPage);
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
        return $this->milestoneRepository->syncTasks($milestone, $taskIds);
    }

    /**
     * Reorder milestones within a project.
     *
     * @param int $projectId
     * @param array $milestoneIds
     * @return bool
     */
    public function reorderMilestones(int $projectId, array $milestoneIds): bool
    {
        return $this->milestoneRepository->reorderMilestones($projectId, $milestoneIds);
    }
}
