<?php
// app/Repositories/PipelineRepository.php

namespace App\Repositories;

use App\Models\Pipeline;
use App\Repositories\Contracts\PipelineRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pipeline Repository Implementation
 */
class PipelineRepository implements PipelineRepositoryInterface
{
    /**
     * Get all pipelines with optional filters.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Pipeline::with(['statuses']);
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }
    
    /**
     * Get all pipelines with pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Pipeline::with(['statuses']);
        
        $this->applyFilters($query, $filters);
        
        return $query->paginate($perPage);
    }

    /**
     * Get pipelines by workspace.
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return Pipeline::with(['statuses'])
                      ->where('workspace_id', $workspaceId)
                      ->get();
    }

    /**
     * Get pipelines by project.
     */
    public function getByProject(int $projectId): Collection
    {
        return Pipeline::with(['statuses'])
                      ->where('project_id', $projectId)
                      ->get();
    }

    /**
     * Find pipeline by ID.
     */
    public function findById(int $id): ?Pipeline
    {
        return Pipeline::with(['statuses'])->find($id);
    }

    /**
     * Find pipeline by slug within workspace.
     */
    public function findBySlug(string $slug, int $workspaceId): ?Pipeline
    {
        return Pipeline::with(['statuses'])
                      ->where('slug', $slug)
                      ->where('workspace_id', $workspaceId)
                      ->first();
    }

    /**
     * Create a new pipeline.
     */
    public function create(array $data): Pipeline
    {
        return Pipeline::create($data);
    }

    /**
     * Update an existing pipeline.
     */
    public function update(Pipeline $pipeline, array $data): Pipeline
    {
        $pipeline->update($data);
        return $pipeline->fresh();
    }

    /**
     * Delete a pipeline.
     */
    public function delete(Pipeline $pipeline): bool
    {
        return $pipeline->delete();
    }

    /**
     * Get default pipeline for a workspace.
     */
    public function getDefaultForWorkspace(int $workspaceId): ?Pipeline
    {
        return Pipeline::with(['statuses'])
                      ->where('workspace_id', $workspaceId)
                      ->where('project_id', null)
                      ->where('is_default', true)
                      ->first();
    }

    /**
     * Get default pipeline for a project.
     */
    public function getDefaultForProject(int $projectId): ?Pipeline
    {
        return Pipeline::with(['statuses'])
                      ->where('project_id', $projectId)
                      ->where('is_default', true)
                      ->first();
    }

    /**
     * Set pipeline as default for workspace or project.
     */
    public function setAsDefault(Pipeline $pipeline): bool
    {
        try {
            DB::beginTransaction();
            
            // Reset other pipelines default status
            if ($pipeline->project_id) {
                // Project-specific pipeline
                Pipeline::where('project_id', $pipeline->project_id)
                       ->where('id', '!=', $pipeline->id)
                       ->update(['is_default' => false]);
            } else {
                // Workspace-level pipeline
                Pipeline::where('workspace_id', $pipeline->workspace_id)
                       ->where('project_id', null)
                       ->where('id', '!=', $pipeline->id)
                       ->update(['is_default' => false]);
            }
            
            // Set this pipeline as default
            $pipeline->update(['is_default' => true]);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Attach status to pipeline.
     */
    public function attachStatus(Pipeline $pipeline, int $statusId, int $order = null): void
    {
        if ($order === null) {
            // Get the next available order number
            $maxOrder = DB::table('pipeline_status')
                         ->where('pipeline_id', $pipeline->id)
                         ->max('order');
            $order = $maxOrder !== null ? $maxOrder + 1 : 0;
        }
        
        $pipeline->statuses()->syncWithoutDetaching([
            $statusId => ['order' => $order]
        ]);
    }

    /**
     * Detach status from pipeline.
     */
    public function detachStatus(Pipeline $pipeline, int $statusId): void
    {
        $pipeline->statuses()->detach($statusId);
    }

    /**
     * Sync statuses to pipeline.
     */
    public function syncStatuses(Pipeline $pipeline, array $statusIds): void
    {
        $syncData = [];
        
        foreach ($statusIds as $index => $statusId) {
            $syncData[$statusId] = ['order' => $index];
        }
        
        $pipeline->statuses()->sync($syncData);
    }
    
    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }
        
        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        
        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Default sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);
    }
}
