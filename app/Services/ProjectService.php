<?php
namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Project Service
 * 
 * Handles all business logic for project operations.
 */
class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {}

    /**
     * Get all projects with filtering and pagination.
     */
    public function getAllProjects(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->getAllPaginated($filters, $perPage);
    }

    /**
     * Get projects by workspace.
     */
    public function getProjectsByWorkspace(int $workspaceId, array $filters = []): Collection
    {
        return $this->projectRepository->getByWorkspace($workspaceId, $filters);
    }

    /**
     * Find project by ID.
     */
    public function findProject(int $id): ?Project
    {
        return $this->projectRepository->findById($id);
    }

    /**
     * Find project by slug within workspace.
     */
    public function findProjectBySlug(string $slug, int $workspaceId): ?Project
    {
        return $this->projectRepository->findBySlug($slug, $workspaceId);
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $data['workspace_id']);
            }

            // Create the project
            $project = $this->projectRepository->create($data);

            // Automatically add owner as project member with owner role
            $this->assignUserToProject($project, $data['owner_id'], 'owner');

            return $project;
        });
    }

    /**
     * Update an existing project.
     */
    public function updateProject(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            // Generate new slug if name changed and slug not provided
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $project->workspace_id, $project->id);
            }

            return $this->projectRepository->update($project, $data);
        });
    }

    /**
     * Delete a project.
     */
    public function deleteProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            // Detach all users first
            $project->users()->detach();
            
            return $this->projectRepository->delete($project);
        });
    }

    /**
     * Activate a project.
     */
    public function activateProject(Project $project): Project
    {
        return $this->updateProject($project, ['status' => 'active']);
    }

    /**
     * Deactivate a project (set to on_hold).
     */
    public function deactivateProject(Project $project): Project
    {
        return $this->updateProject($project, ['status' => 'on_hold']);
    }

    /**
     * Complete a project.
     */
    public function completeProject(Project $project): Project
    {
        return $this->updateProject($project, [
            'status' => 'completed',
            'progress' => 100
        ]);
    }

    /**
     * Cancel a project.
     */
    public function cancelProject(Project $project): Project
    {
        return $this->updateProject($project, ['status' => 'cancelled']);
    }

    /**
     * Assign user to project.
     */
    public function assignUserToProject(Project $project, int $userId, string $role = 'contributor'): void
    {
        $this->projectRepository->attachUser($project, $userId, $role);
    }

    /**
     * Remove user from project.
     */
    public function removeUserFromProject(Project $project, int $userId): void
    {
        $this->projectRepository->detachUser($project, $userId);
    }

    /**
     * Update user role in project.
     */
    public function updateUserRole(Project $project, int $userId, string $role): void
    {
        $this->projectRepository->updateUserRole($project, $userId, $role);
    }

    /**
     * Update project progress.
     */
    public function updateProgress(Project $project, int $progress): Project
    {
        $progress = max(0, min(100, $progress)); // Ensure progress is between 0-100

        $data = ['progress' => $progress];

        // Automatically complete project if progress reaches 100%
        if ($progress === 100 && $project->status !== 'completed') {
            $data['status'] = 'completed';
        }

        return $this->updateProject($project, $data);
    }

    /**
     * Get project statistics.
     */
    public function getProjectStatistics(?int $workspaceId = null): array
    {
        $baseQuery = Project::query();
        
        if ($workspaceId) {
            $baseQuery->where('workspace_id', $workspaceId);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'on_hold' => (clone $baseQuery)->where('status', 'on_hold')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'high_priority' => (clone $baseQuery)->where('priority', 'high')->count(),
            'urgent_priority' => (clone $baseQuery)->where('priority', 'urgent')->count(),
        ];
    }

    /**
     * Generate unique slug for project within workspace.
     */
    private function generateUniqueSlug(string $name, int $workspaceId, ?int $excludeId = null): string
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
    private function slugExists(string $slug, int $workspaceId, ?int $excludeId = null): bool
    {
        $query = Project::where('slug', $slug)->where('workspace_id', $workspaceId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
