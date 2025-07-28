<?php
// app/Services/PipelineService.php

namespace App\Services;

use App\Http\Resources\PipelineResource;
use App\Models\Pipeline;
use App\Repositories\Contracts\PipelineRepositoryInterface;
use App\Repositories\Contracts\StatusRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pipeline Service
 * 
 * Handles business logic for pipelines.
 */
class PipelineService
{
    /**
     * Constructor.
     */
    public function __construct(
        private PipelineRepositoryInterface $pipelineRepository,
        private StatusRepositoryInterface $statusRepository
    ) {}

    /**
     * Get all pipelines with optional filtering.
     */
    public function getAllPipelines(array $filters = []): Collection
    {
        return $this->pipelineRepository->getAll($filters);
    }
    
    /**
     * Get all pipelines with pagination.
     */
    public function getAllPipelinesPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->pipelineRepository->getAllPaginated($filters, $perPage);
    }

    /**
     * Get pipelines by workspace.
     */
    public function getPipelinesByWorkspace(int $workspaceId): Collection
    {
        return $this->pipelineRepository->getByWorkspace($workspaceId);
    }

    /**
     * Get pipelines by project.
     */
    public function getPipelinesByProject(int $projectId): Collection
    {
        return $this->pipelineRepository->getByProject($projectId);
    }

    /**
     * Find pipeline by ID.
     */
    public function findPipeline(int $id): ?Pipeline
    {
        return $this->pipelineRepository->findById($id);
    }

    /**
     * Find pipeline by slug within workspace.
     */
    public function findPipelineBySlug(string $slug, int $workspaceId): ?Pipeline
    {
        return $this->pipelineRepository->findBySlug($slug, $workspaceId);
    }

    /**
     * Create a new pipeline.
     */
    public function createPipeline(array $data, array $statusIds = []): Pipeline
    {
        return DB::transaction(function () use ($data, $statusIds) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $data['workspace_id']);
            }
            
            // Create pipeline
            $pipeline = $this->pipelineRepository->create($data);
            
            // Attach statuses if provided
            if (!empty($statusIds)) {
                foreach ($statusIds as $index => $statusId) {
                    $this->pipelineRepository->attachStatus($pipeline, $statusId, $index);
                }
            } else {
                // If no statuses provided, attach default statuses from workspace
                $defaultStatuses = $this->statusRepository->getDefaultStatuses($data['workspace_id']);
                
                foreach ($defaultStatuses as $index => $status) {
                    $this->pipelineRepository->attachStatus($pipeline, $status->id, $status->order);
                }
            }
            
            // If this is the first pipeline for this workspace/project, set as default
            $this->setDefaultIfNeeded($pipeline);
            
            return $pipeline->fresh(['statuses']);
        });
    }

    /**
     * Update an existing pipeline.
     */
    public function updatePipeline(Pipeline $pipeline, array $data): Pipeline
    {
        return DB::transaction(function () use ($pipeline, $data) {
            // Generate new slug if name changed and slug not provided
            if (isset($data['name']) && !isset($data['slug']) && $data['name'] !== $pipeline->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $pipeline->workspace_id, $pipeline->id);
            }

            return $this->pipelineRepository->update($pipeline, $data);
        });
    }

    /**
     * Delete a pipeline.
     */
    public function deletePipeline(Pipeline $pipeline): bool
    {
        return DB::transaction(function () use ($pipeline) {
            // First detach all statuses
            $pipeline->statuses()->detach();
            
            // Then delete the pipeline
            return $this->pipelineRepository->delete($pipeline);
        });
    }

    /**
     * Set pipeline as default.
     */
    public function setAsDefault(Pipeline $pipeline): bool
    {
        return $this->pipelineRepository->setAsDefault($pipeline);
    }

    /**
     * Sync statuses to pipeline.
     */
    public function syncStatuses(Pipeline $pipeline, array $statusIds): Pipeline
    {
        $this->pipelineRepository->syncStatuses($pipeline, $statusIds);
        return $pipeline->fresh(['statuses']);
    }

    /**
     * Add status to pipeline.
     */
    public function addStatus(Pipeline $pipeline, int $statusId, ?int $order = null): Pipeline
    {
        $this->pipelineRepository->attachStatus($pipeline, $statusId, $order);
        return $pipeline->fresh(['statuses']);
    }

    /**
     * Remove status from pipeline.
     */
    public function removeStatus(Pipeline $pipeline, int $statusId): Pipeline
    {
        $this->pipelineRepository->detachStatus($pipeline, $statusId);
        return $pipeline->fresh(['statuses']);
    }

    /**
     * Reorder statuses in pipeline.
     */
    public function reorderStatuses(Pipeline $pipeline, array $statusesOrder): Pipeline
    {
        DB::transaction(function () use ($pipeline, $statusesOrder) {
            // Reorder the statuses
            $this->statusRepository->reorderInPipeline($pipeline->id, $statusesOrder);
        });
        
        return $pipeline->fresh(['statuses']);
    }
    
    /**
     * Create default pipeline for workspace.
     */
    public function createDefaultPipelineForWorkspace(int $workspaceId): Pipeline
    {
        return DB::transaction(function () use ($workspaceId) {
            $pipelineData = [
                'workspace_id' => $workspaceId,
                'project_id' => null,
                'name' => 'Default Pipeline',
                'slug' => 'default-pipeline',
                'description' => 'Default workspace pipeline',
                'is_default' => true,
            ];
            
            $pipeline = $this->pipelineRepository->create($pipelineData);
            
            // Attach default statuses
            $defaultStatuses = $this->statusRepository->getDefaultStatuses($workspaceId);
            
            foreach ($defaultStatuses as $index => $status) {
                $this->pipelineRepository->attachStatus($pipeline, $status->id, $status->order);
            }
            
            return $pipeline->fresh(['statuses']);
        });
    }
    
    /**
     * Get default pipeline for a workspace or create one if it doesn't exist.
     */
    public function getOrCreateDefaultPipelineForWorkspace(int $workspaceId): Pipeline
    {
        $defaultPipeline = $this->pipelineRepository->getDefaultForWorkspace($workspaceId);
        
        if (!$defaultPipeline) {
            $defaultPipeline = $this->createDefaultPipelineForWorkspace($workspaceId);
        }
        
        return $defaultPipeline;
    }

    /**
     * Set pipeline as default if needed.
     */
    private function setDefaultIfNeeded(Pipeline $pipeline): void
    {
        if ($pipeline->project_id) {
            // Check if this is the first pipeline for this project
            $count = Pipeline::where('project_id', $pipeline->project_id)->count();
            if ($count === 1) {
                $this->pipelineRepository->setAsDefault($pipeline);
            }
        } else {
            // Check if this is the first workspace-level pipeline
            $count = Pipeline::where('workspace_id', $pipeline->workspace_id)
                           ->where('project_id', null)
                           ->count();
            if ($count === 1) {
                $this->pipelineRepository->setAsDefault($pipeline);
            }
        }
    }

    /**
     * Generate unique slug for pipeline within workspace.
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
        $query = Pipeline::where('slug', $slug)->where('workspace_id', $workspaceId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    
        public function getDefaultForProject(int $projectId): \Illuminate\Http\JsonResponse
        {
            $return = $this->pipelineRepository->getDefaultForProject($projectId);
            if (!$return) {
                return response()->json(['message' => 'Default pipeline not found for this project'], 404);
            }

            return response()->json(['data' => new PipelineResource($return)]);
        }
}
