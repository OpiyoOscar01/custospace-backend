<?php
// app/Repositories/Contracts/ProjectRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Project Repository Interface
 * 
 * Defines the contract for project data access operations.
 */
interface ProjectRepositoryInterface
{
    /**
     * Get all projects with optional filters and pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get projects by workspace.
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection;

    /**
     * Find project by ID.
     */
    public function findById(int $id): ?Project;

    /**
     * Find project by slug within workspace.
     */
    public function findBySlug(string $slug, int $workspaceId): ?Project;

    /**
     * Create a new project.
     */
    public function create(array $data): Project;

    /**
     * Update an existing project.
     */
    public function update(Project $project, array $data): Project;

    /**
     * Delete a project.
     */
    public function delete(Project $project): bool;

    /**
     * Get projects by status.
     */
    public function getByStatus(string $status, ?int $workspaceId = null): Collection;

    /**
     * Get projects by priority.
     */
    public function getByPriority(string $priority, ?int $workspaceId = null): Collection;

    /**
     * Get active projects.
     */
    public function getActive(?int $workspaceId = null): Collection;

    /**
     * Attach user to project.
     */
    public function attachUser(Project $project, int $userId, string $role = 'contributor'): void;

    /**
     * Detach user from project.
     */
    public function detachUser(Project $project, int $userId): void;

    /**
     * Update user role in project.
     */
    public function updateUserRole(Project $project, int $userId, string $role): void;
}
