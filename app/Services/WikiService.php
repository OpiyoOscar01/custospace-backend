<?php

namespace App\Services;

use App\Models\Wiki;
use App\Models\User;
use App\Repositories\Contracts\WikiRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Wiki Service - Handles business logic for wiki operations
 */
class WikiService
{
    public function __construct(
        private WikiRepositoryInterface $wikiRepository
    ) {}

    /**
     * Get paginated wikis with caching and filters.
     */
    public function getPaginatedWikis(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->wikiRepository->getPaginated($filters, $perPage);
        } catch (\Exception $e) {
            Log::error('Error fetching paginated wikis', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get wiki by ID with caching.
     */
    public function getWikiById(int $id): ?Wiki
    {
        $cacheKey = "wiki.{$id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($id) {
            return $this->wikiRepository->findWithRelations($id, ['revisions' => function ($query) {
                $query->latest()->limit(5);
            }]);
        });
    }

    /**
     * Get wiki by slug within workspace.
     */
    public function getWikiBySlug(int $workspaceId, string $slug): ?Wiki
    {
        $cacheKey = "wiki.workspace.{$workspaceId}.slug.{$slug}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($workspaceId, $slug) {
            return $this->wikiRepository->findBySlug($workspaceId, $slug);
        });
    }

    /**
     * Create new wiki with business logic.
     */
    public function createWiki(array $data): Wiki
    {
        try {
            // Validate workspace access (should be done in controller/policy)
            $this->validateWorkspaceAccess($data['workspace_id'], $data['created_by_id']);

            // Validate parent hierarchy if parent_id is provided
            if (!empty($data['parent_id'])) {
                $this->validateParentHierarchy($data['parent_id'], $data['workspace_id']);
            }

            $wiki = $this->wikiRepository->create($data);

            // Clear related caches
            $this->clearWikiCaches($wiki);

            Log::info('Wiki created successfully', ['wiki_id' => $wiki->id]);

            return $wiki;
        } catch (\Exception $e) {
            Log::error('Error creating wiki', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update existing wiki with business logic.
     */
    public function updateWiki(Wiki $wiki, array $data): bool
    {
        try {
            // Validate parent hierarchy if changing parent
            if (isset($data['parent_id']) && $data['parent_id'] !== $wiki->parent_id) {
                $this->validateParentHierarchy($data['parent_id'], $wiki->workspace_id, $wiki->id);
            }

            $updated = $this->wikiRepository->update($wiki, $data);

            if ($updated) {
                // Clear related caches
                $this->clearWikiCaches($wiki);
                
                Log::info('Wiki updated successfully', ['wiki_id' => $wiki->id]);
            }

            return $updated;
        } catch (\Exception $e) {
            Log::error('Error updating wiki', [
                'wiki_id' => $wiki->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete wiki with business logic.
     */
    public function deleteWiki(Wiki $wiki): bool
    {
        try {
            // Check if wiki has children and handle accordingly
            $childrenCount = $wiki->children()->count();
            if ($childrenCount > 0) {
                Log::info("Wiki has {$childrenCount} children, moving to parent", [
                    'wiki_id' => $wiki->id
                ]);
            }

            $deleted = $this->wikiRepository->delete($wiki);

            if ($deleted) {
                // Clear related caches
                $this->clearWikiCaches($wiki);
                
                Log::info('Wiki deleted successfully', ['wiki_id' => $wiki->id]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error deleting wiki', [
                'wiki_id' => $wiki->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Publish/unpublish wiki.
     */
    public function toggleWikiPublication(Wiki $wiki): bool
    {
        try {
            $toggled = $this->wikiRepository->togglePublication($wiki);

            if ($toggled) {
                // Clear caches
                $this->clearWikiCaches($wiki);
                
                $status = $wiki->fresh()->is_published ? 'published' : 'unpublished';
                Log::info("Wiki {$status} successfully", ['wiki_id' => $wiki->id]);
            }

            return $toggled;
        } catch (\Exception $e) {
            Log::error('Error toggling wiki publication', [
                'wiki_id' => $wiki->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get wiki tree structure for workspace.
     */
    public function getWikiTree(int $workspaceId): Collection
    {
        $cacheKey = "wiki.tree.workspace.{$workspaceId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($workspaceId) {
            return $this->wikiRepository->getTreeByWorkspace($workspaceId);
        });
    }

    /**
     * Search wikis within workspace.
     */
    public function searchWikis(int $workspaceId, string $query): Collection
    {
        try {
            return $this->wikiRepository->search($workspaceId, $query);
        } catch (\Exception $e) {
            Log::error('Error searching wikis', [
                'workspace_id' => $workspaceId,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get wiki breadcrumb navigation.
     */
    public function getWikiBreadcrumb(Wiki $wiki): array
    {
        $cacheKey = "wiki.breadcrumb.{$wiki->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($wiki) {
            return $this->wikiRepository->getBreadcrumb($wiki);
        });
    }

    /**
     * Assign user to wiki (for collaboration).
     */
    public function assignUserToWiki(Wiki $wiki, User $user, string $role = 'collaborator'): bool
    {
        try {
            // This would typically involve a pivot table for wiki_user_roles
            // For now, we'll add to metadata
            $metadata = $wiki->metadata ?? [];
            $metadata['collaborators'] = $metadata['collaborators'] ?? [];
            
            $collaboratorExists = collect($metadata['collaborators'])
                ->contains('user_id', $user->id);

            if (!$collaboratorExists) {
                $metadata['collaborators'][] = [
                    'user_id' => $user->id,
                    'role' => $role,
                    'assigned_at' => now()->toISOString(),
                ];

                $updated = $wiki->update(['metadata' => $metadata]);

                if ($updated) {
                    $this->clearWikiCaches($wiki);
                    
                    Log::info('User assigned to wiki', [
                        'wiki_id' => $wiki->id,
                        'user_id' => $user->id,
                        'role' => $role
                    ]);
                }

                return $updated;
            }

            return true; // Already assigned
        } catch (\Exception $e) {
            Log::error('Error assigning user to wiki', [
                'wiki_id' => $wiki->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate workspace access for user.
     */
    private function validateWorkspaceAccess(int $workspaceId, int $userId): void
    {
        // This should check if user has access to workspace
        // Implementation depends on your workspace access logic
    }

    /**
     * Validate parent hierarchy to prevent circular references.
     */
    private function validateParentHierarchy(?int $parentId, int $workspaceId, ?int $excludeId = null): void
    {
        if (!$parentId) {
            return;
        }

        $parent = Wiki::where('id', $parentId)
            ->where('workspace_id', $workspaceId)
            ->first();

        if (!$parent) {
            throw new \InvalidArgumentException('Parent wiki not found in workspace.');
        }

        // Check for circular reference
        if ($excludeId && $this->wouldCreateCircularReference($parentId, $excludeId)) {
            throw new \InvalidArgumentException('Cannot create circular reference in wiki hierarchy.');
        }
    }

    /**
     * Check if setting parent would create circular reference.
     */
    private function wouldCreateCircularReference(int $parentId, int $childId): bool
    {
        $currentParent = Wiki::find($parentId);
        
        while ($currentParent) {
            if ($currentParent->id === $childId) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }

        return false;
    }

    /**
     * Clear wiki-related caches.
     */
    private function clearWikiCaches(Wiki $wiki): void
    {
        $tags = [
            "wiki.{$wiki->id}",
            "wiki.workspace.{$wiki->workspace_id}.slug.{$wiki->slug}",
            "wiki.tree.workspace.{$wiki->workspace_id}",
            "wiki.breadcrumb.{$wiki->id}",
        ];

        foreach ($tags as $tag) {
            Cache::forget($tag);
        }
    }
}
