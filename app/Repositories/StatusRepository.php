<?php
// app/Repositories/StatusRepository.php

namespace App\Repositories;

use App\Models\Status;
use App\Repositories\Contracts\StatusRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Status Repository Implementation
 */
class StatusRepository implements StatusRepositoryInterface
{
    /**
     * Get all statuses with optional filters.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Status::query();
        
        $this->applyFilters($query, $filters);
        
        // Apply ordering
        $sortBy = $filters['sort_by'] ?? 'order';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);
        
        return $query->get();
    }
    
    /**
     * Get all statuses with pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Status::query();
        
        $this->applyFilters($query, $filters);
        
        // Apply ordering
        $sortBy = $filters['sort_by'] ?? 'order';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);
        
        return $query->paginate($perPage);
    }

    /**
     * Get statuses by workspace.
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection
    {
        $query = Status::where('workspace_id', $workspaceId);
        
        $this->applyFilters($query, $filters);
        
        // Apply ordering
        $sortBy = $filters['sort_by'] ?? 'order';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);
        
        return $query->get();
    }

    /**
     * Find status by ID.
     */
    public function findById(int $id): ?Status
    {
        return Status::find($id);
    }

    /**
     * Find status by slug within workspace.
     */
    public function findBySlug(string $slug, int $workspaceId): ?Status
    {
        return Status::where('slug', $slug)
                    ->where('workspace_id', $workspaceId)
                    ->first();
    }

    /**
     * Create a new status.
     */
    public function create(array $data): Status
    {
        if (!isset($data['order'])) {
            // Get the next available order number
            $maxOrder = Status::where('workspace_id', $data['workspace_id'])->max('order');
            $data['order'] = $maxOrder ? $maxOrder + 1 : 0;
        }
        
        return Status::create($data);
    }

    /**
     * Update an existing status.
     */
    public function update(Status $status, array $data): Status
    {
        $status->update($data);
        return $status->fresh();
    }

    /**
     * Delete a status.
     */
    public function delete(Status $status): bool
    {
        return $status->delete();
    }

    /**
     * Get default statuses for a workspace.
     */
    public function getDefaultStatuses(int $workspaceId): Collection
    {
        return Status::where('workspace_id', $workspaceId)
                    ->where('is_default', true)
                    ->orderBy('order')
                    ->get();
    }

    /**
     * Get statuses by type.
     */
    public function getByType(string $type, int $workspaceId = null): Collection
    {
        $query = Status::where('type', $type);
        
        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        }
        
        return $query->orderBy('order')->get();
    }

    /**
     * Reorder statuses in a pipeline.
     */
    public function reorderInPipeline(int $pipelineId, array $statusesOrder): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($statusesOrder as $statusId => $order) {
                DB::table('pipeline_status')
                    ->where('pipeline_id', $pipelineId)
                    ->where('status_id', $statusId)
                    ->update(['order' => $order]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
    
    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
    }
}
