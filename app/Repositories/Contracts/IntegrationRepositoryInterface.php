<?php

namespace App\Repositories\Contracts;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Integration Repository Interface
 * 
 * Defines contract for integration data access operations
 */
interface IntegrationRepositoryInterface
{
    /**
     * Get all integrations with optional filters
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated integrations
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find integration by ID
     */
    public function find(int $id): ?Integration;

    /**
     * Create new integration
     */
    public function create(array $data): Integration;

    /**
     * Update existing integration
     */
    public function update(Integration $integration, array $data): Integration;

    /**
     * Delete integration
     */
    public function delete(Integration $integration): bool;

    /**
     * Get integrations by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get integrations by type
     */
    public function getByType(string $type): Collection;

    /**
     * Get active integrations
     */
    public function getActive(): Collection;
}
