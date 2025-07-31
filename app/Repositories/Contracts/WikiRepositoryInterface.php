<?php

namespace App\Repositories\Contracts;

use App\Models\Wiki;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Wiki Repository Interface - Defines contract for wiki data access
 */
interface WikiRepositoryInterface
{
    /**
     * Get paginated wikis with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find wiki by ID with relations.
     */
    public function findWithRelations(int $id, array $relations = []): ?Wiki;

    /**
     * Find wiki by slug within workspace.
     */
    public function findBySlug(int $workspaceId, string $slug): ?Wiki;

    /**
     * Create new wiki.
     */
    public function create(array $data): Wiki;

    /**
     * Update existing wiki.
     */
    public function update(Wiki $wiki, array $data): bool;

    /**
     * Delete wiki.
     */
    public function delete(Wiki $wiki): bool;

    /**
     * Get wiki tree structure for workspace.
     */
    public function getTreeByWorkspace(int $workspaceId): Collection;

    /**
     * Get published wikis by workspace.
     */
    public function getPublishedByWorkspace(int $workspaceId): Collection;

    /**
     * Search wikis by content.
     */
    public function search(int $workspaceId, string $query): Collection;

    /**
     * Get wiki breadcrumb path.
     */
    public function getBreadcrumb(Wiki $wiki): array;

    /**
     * Toggle wiki publication status.
     */
    public function togglePublication(Wiki $wiki): bool;
}
