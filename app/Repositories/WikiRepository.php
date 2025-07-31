<?php

namespace App\Repositories;

use App\Models\Wiki;
use App\Repositories\Contracts\WikiRepositoryInterface;
use App\Services\WikiRevisionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Wiki Repository - Handles wiki data access operations
 */
class WikiRepository implements WikiRepositoryInterface
{
    /**
     * Get paginated wikis with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Wiki::with(['workspace', 'createdBy', 'parent'])
            ->latest();

        // Apply filters
        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (!empty($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['created_by_id'])) {
            $query->where('created_by_id', $filters['created_by_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find wiki by ID with relations.
     */
    public function findWithRelations(int $id, array $relations = []): ?Wiki
    {
        $defaultRelations = ['workspace', 'createdBy', 'parent', 'children'];
        $relations = array_merge($defaultRelations, $relations);

        return Wiki::with($relations)->find($id);
    }

    /**
     * Find wiki by slug within workspace.
     */
    public function findBySlug(int $workspaceId, string $slug): ?Wiki
    {
        return Wiki::where('workspace_id', $workspaceId)
            ->where('slug', $slug)
            ->with(['workspace', 'createdBy', 'parent', 'children'])
            ->first();
    }

    /**
     * Create new wiki.
     */
    public function create(array $data): Wiki
    {
        return DB::transaction(function () use ($data) {
            $wiki = Wiki::create($data);
            
            // Create initial revision if content is provided
            if (!empty($data['content'])) {
                $wiki->revisions()->create([
                    'user_id' => $data['created_by_id'],
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'summary' => $data['revision_summary'] ?? 'Initial version',
                ]);
            }

            return $wiki->load(['workspace', 'createdBy', 'parent']);
        });
    }

    /**
     * Update existing wiki.
     */
  /**
 * Update existing wiki with intelligent revision creation.
 */
public function update(Wiki $wiki, array $data): bool
{
    return DB::transaction(function () use ($wiki, $data) {
        $originalTitle = $wiki->title;
        $originalContent = $wiki->content;
        
        $updated = $wiki->update($data);

        // Check if we should create a revision
        $revisionService = app(WikiRevisionService::class);
        
        $shouldCreateRevision = $revisionService->shouldCreateRevision(
            $originalContent,
            $data['content'] ?? $wiki->content,
            $originalTitle,
            $data['title'] ?? $wiki->title
        );

        if ($shouldCreateRevision) {
            $revisionService->createRevision($wiki, [
                'title' => $data['title'] ?? $wiki->title,
                'content' => $data['content'] ?? $wiki->content,
                'summary' => $data['revision_summary'] ?? 'Content updated',
            ]);
        }

        return $updated;
    });
}


    /**
     * Delete wiki.
     */
    public function delete(Wiki $wiki): bool
    {
        return DB::transaction(function () use ($wiki) {
            // Move children to parent or root
            $wiki->children()->update(['parent_id' => $wiki->parent_id]);
            
            return $wiki->delete();
        });
    }

    /**
     * Get wiki tree structure for workspace.
     */
    public function getTreeByWorkspace(int $workspaceId): Collection
    {
        return Wiki::where('workspace_id', $workspaceId)
            ->with(['children' => function ($query) {
                $query->orderBy('title');
            }])
            ->whereNull('parent_id')
            ->orderBy('title')
            ->get();
    }

    /**
     * Get published wikis by workspace.
     */
    public function getPublishedByWorkspace(int $workspaceId): Collection
    {
        return Wiki::where('workspace_id', $workspaceId)
            ->where('is_published', true)
            ->with(['createdBy', 'parent'])
            ->orderBy('title')
            ->get();
    }

    /**
     * Search wikis by content.
     */
    public function search(int $workspaceId, string $query): Collection
    {
        return Wiki::where('workspace_id', $workspaceId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%')
                  ->orWhereJsonContains('metadata->tags', $query);
            })
            ->with(['createdBy', 'parent'])
            ->orderBy('title')
            ->get();
    }

    /**
     * Get wiki breadcrumb path.
     */
    public function getBreadcrumb(Wiki $wiki): array
    {
        $breadcrumb = [];
        $current = $wiki;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'title' => $current->title,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    /**
     * Toggle wiki publication status.
     */
    public function togglePublication(Wiki $wiki): bool
    {
        return $wiki->update(['is_published' => !$wiki->is_published]);
    }
}
