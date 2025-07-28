<?php
// app/Repositories/Contracts/PipelineRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\Pipeline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pipeline Repository Interface
 * 
 * Defines the contract for pipeline data access operations.
 */
interface PipelineRepositoryInterface
{
    /**
     * Get all pipelines with optional filters.
     */
    public function getAll(array $filters = []): Collection;
    
    /**
     * Get all pipelines with pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get pipelines by workspace.
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get pipelines by project.
     */
    public function getByProject(int $projectId): Collection;

    /**
     * Find pipeline by ID.
     */
    public function findById(int $id): ?Pipeline;

    /**
     * Find pipeline by slug within workspace.
     */
    public function findBySlug(string $slug, int $workspaceId): ?Pipeline;

    /**
     * Create a new pipeline.
     */
    public function create(array $data): Pipeline;

    /**
     * Update an existing pipeline.
     */
    public function update(Pipeline $pipeline, array $data): Pipeline;

    /**
     * Delete a pipeline.
     */
    public function delete(Pipeline $pipeline): bool;

    /**
     * Get default pipeline for a workspace.
     */
    public function getDefaultForWorkspace(int $workspaceId): ?Pipeline;

    /**
     * Get default pipeline for a project.
     */
    public function getDefaultForProject(int $projectId): ?Pipeline;

    /**
     * Set pipeline as default for workspace or project.
     */
    public function setAsDefault(Pipeline $pipeline): bool;

    /**
     * Attach status to pipeline.
     */
    public function attachStatus(Pipeline $pipeline, int $statusId, ?int $order = null): void;

    /**
     * Detach status from pipeline.
     */
    public function detachStatus(Pipeline $pipeline, int $statusId): void;

    /**
     * Sync statuses to pipeline.
     */
    public function syncStatuses(Pipeline $pipeline, array $statusIds): void;
}
