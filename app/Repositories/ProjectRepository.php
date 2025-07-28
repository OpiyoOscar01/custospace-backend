<?php
// app/Repositories/ProjectRepository.php

namespace App\Repositories;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Project Repository Implementation
 * 
 * Handles all database operations for projects.
 */
class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Get all projects with optional filters and pagination.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Project::with(['workspace', 'team', 'owner', 'users']);

        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_template'])) {
            $query->where('is_template', $filters['is_template']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get projects by workspace.
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection
    {
        $query = Project::with(['team', 'owner', 'users'])
                        ->where('workspace_id', $workspaceId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->get();
    }

    /**
     * Find project by ID.
     */
    public function findById(int $id): ?Project
    {
        return Project::with(['workspace', 'team', 'owner', 'users', 'pipelines'])
                      ->find($id);
    }

    /**
     * Find project by slug within workspace.
     */
    public function findBySlug(string $slug, int $workspaceId): ?Project
    {
        return Project::with(['workspace', 'team', 'owner', 'users', 'pipelines'])
                      ->where('slug', $slug)
                      ->where('workspace_id', $workspaceId)
                      ->first();
    }

    /**
     * Create a new project.
     */
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Update an existing project.
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }

    /**
     * Delete a project.
     */
    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    /**
     * Get projects by status.
     */
    public function getByStatus(string $status, ?int $workspaceId = null): Collection
    {
        $query = Project::with(['team', 'owner'])->where('status', $status);

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get();
    }

    /**
     * Get projects by priority.
     */
    public function getByPriority(string $priority, ?int $workspaceId = null): Collection
    {
        $query = Project::with(['team', 'owner'])->where('priority', $priority);

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get();
    }

    /**
     * Get active projects.
     */
    public function getActive(?int $workspaceId = null): Collection
    {
        return $this->getByStatus('active', $workspaceId);
    }

    /**
     * Attach user to project.
     */
    public function attachUser(Project $project, int $userId, string $role = 'contributor'): void
    {
        $project->users()->syncWithoutDetaching([
            $userId => ['role' => $role]
        ]);
    }

    /**
     * Detach user from project.
     */
    public function detachUser(Project $project, int $userId): void
    {
        $project->users()->detach($userId);
    }

    /**
     * Update user role in project.
     */
    public function updateUserRole(Project $project, int $userId, string $role): void
    {
        $project->users()->updateExistingPivot($userId, ['role' => $role]);
    }
}
