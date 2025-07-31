<?php

namespace App\Repositories;

use App\Models\Integration;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Integration Repository Implementation
 * 
 * Handles all database operations for integrations
 */
class IntegrationRepository implements IntegrationRepositoryInterface
{
    /**
     * Integration model instance
     */
    protected Integration $model;

    public function __construct(Integration $model)
    {
        $this->model = $model;
    }

    /**
     * Get all integrations with optional filters
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->with('workspace')->get();
    }

    /**
     * Get paginated integrations
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->with('workspace')->paginate($perPage);
    }

    /**
     * Find integration by ID
     */
    public function find(int $id): ?Integration
    {
        return $this->model->with('workspace')->find($id);
    }

    /**
     * Create new integration
     */
    public function create(array $data): Integration
    {
        return $this->model->create($data);
    }

    /**
     * Update existing integration
     */
    public function update(Integration $integration, array $data): Integration
    {
        $integration->update($data);
        return $integration->fresh();
    }

    /**
     * Delete integration
     */
    public function delete(Integration $integration): bool
    {
        return $integration->delete();
    }

    /**
     * Get integrations by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return $this->model->where('workspace_id', $workspaceId)->get();
    }

    /**
     * Get integrations by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get active integrations
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }
}
