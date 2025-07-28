<?php
// app/Services/StatusService.php

namespace App\Services;

use App\Models\Status;
use App\Repositories\Contracts\StatusRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Status Service
 * 
 * Handles business logic for statuses.
 */
class StatusService
{
    /**
     * Constructor.
     */
    public function __construct(
        private StatusRepositoryInterface $statusRepository
    ) {}

    /**
     * Get all statuses with optional filtering.
     */
    public function getAllStatuses(array $filters = []): Collection
    {
        return $this->statusRepository->getAll($filters);
    }
    
    /**
     * Get all statuses with pagination.
     */
    public function getAllStatusesPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->statusRepository->getAllPaginated($filters, $perPage);
    }

    /**
     * Get statuses by workspace.
     */
    public function getStatusesByWorkspace(int $workspaceId, array $filters = []): Collection
    {
        return $this->statusRepository->getByWorkspace($workspaceId, $filters);
    }

    /**
     * Find status by ID.
     */
    public function findStatus(int $id): ?Status
    {
        return $this->statusRepository->findById($id);
    }

    /**
     * Find status by slug within workspace.
     */
    public function findStatusBySlug(string $slug, int $workspaceId): ?Status
    {
        return $this->statusRepository->findBySlug($slug, $workspaceId);
    }

    /**
     * Create a new status.
     */
    public function createStatus(array $data): Status
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $data['workspace_id']);
            }

            return $this->statusRepository->create($data);
        });
    }

    /**
     * Update an existing status.
     */
    public function updateStatus(Status $status, array $data): Status
    {
        return DB::transaction(function () use ($status, $data) {
            // Generate new slug if name changed and slug not provided
            if (isset($data['name']) && !isset($data['slug']) && $data['name'] !== $status->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $status->workspace_id, $status->id);
            }

            return $this->statusRepository->update($status, $data);
        });
    }

    /**
     * Delete a status.
     */
    public function deleteStatus(Status $status): bool
    {
        return $this->statusRepository->delete($status);
    }

    /**
     * Get default statuses for a workspace.
     */
    public function getDefaultStatuses(int $workspaceId): Collection
    {
        return $this->statusRepository->getDefaultStatuses($workspaceId);
    }

    /**
     * Get statuses by type.
     */
    public function getStatusesByType(string $type, int $workspaceId = null): Collection
    {
        return $this->statusRepository->getByType($type, $workspaceId);
    }

    /**
     * Reorder statuses in a pipeline.
     */
    public function reorderStatusesInPipeline(int $pipelineId, array $statusesOrder): bool
    {
        return $this->statusRepository->reorderInPipeline($pipelineId, $statusesOrder);
    }
    
    /**
     * Create default statuses for a workspace.
     */
    public function createDefaultStatuses(int $workspaceId): Collection
    {
        return DB::transaction(function () use ($workspaceId) {
            $defaultStatuses = [
                [
                    'name' => 'Backlog',
                    'color' => '#6B7280',
                    'type' => 'backlog',
                    'order' => 0,
                    'is_default' => true,
                ],
                [
                    'name' => 'To Do',
                    'color' => '#3B82F6',
                    'type' => 'todo',
                    'order' => 1,
                    'is_default' => true,
                ],
                [
                    'name' => 'In Progress',
                    'color' => '#F59E0B',
                    'type' => 'in_progress',
                    'order' => 2,
                    'is_default' => true,
                ],
                [
                    'name' => 'Done',
                    'color' => '#10B981',
                    'type' => 'done',
                    'order' => 3,
                    'is_default' => true,
                ],
                [
                    'name' => 'Cancelled',
                    'color' => '#EF4444',
                    'type' => 'cancelled',
                    'order' => 4,
                    'is_default' => true,
                ],
            ];
            
            $createdStatuses = collect();
            
            foreach ($defaultStatuses as $statusData) {
                $statusData['workspace_id'] = $workspaceId;
                $statusData['slug'] = Str::slug($statusData['name']);
                
                $createdStatuses->push($this->statusRepository->create($statusData));
            }
            
            return $createdStatuses;
        });
    }

    /**
     * Generate unique slug for status within workspace.
     */
    private function generateUniqueSlug(string $name, int $workspaceId, int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $workspaceId, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists in workspace.
     */
    private function slugExists(string $slug, int $workspaceId, int $excludeId = null): bool
    {
        $query = Status::where('slug', $slug)->where('workspace_id', $workspaceId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
