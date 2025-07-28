<?php
// app/Repositories/Contracts/StatusRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\Status;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Status Repository Interface
 * 
 * Defines the contract for status data access operations.
 */
interface StatusRepositoryInterface
{
    /**
     * Get all statuses with optional filters.
     */
    public function getAll(array $filters = []): Collection;
    
    /**
     * Get all statuses with pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get statuses by workspace.
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection;

    /**
     * Find status by ID.
     */
    public function findById(int $id): ?Status;

    /**
     * Find status by slug within workspace.
     */
    public function findBySlug(string $slug, int $workspaceId): ?Status;

    /**
     * Create a new status.
     */
    public function create(array $data): Status;

    /**
     * Update an existing status.
     */
    public function update(Status $status, array $data): Status;

    /**
     * Delete a status.
     */
    public function delete(Status $status): bool;

    /**
     * Get default statuses for a workspace.
     */
    public function getDefaultStatuses(int $workspaceId): Collection;

    /**
     * Get statuses by type.
     */
    public function getByType(string $type, ?int $workspaceId = null): Collection;

    /**
     * Reorder statuses in a pipeline.
     */
    public function reorderInPipeline(int $pipelineId, array $statusesOrder): bool;
}
