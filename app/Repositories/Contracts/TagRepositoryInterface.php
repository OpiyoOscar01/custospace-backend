<?php

namespace App\Repositories\Contracts;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TagRepositoryInterface
{
    /**
     * Get all tags by workspace.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getAllByWorkspace(int $workspaceId): Collection;

    /**
     * Get paginated tags by workspace.
     *
     * @param int $workspaceId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedByWorkspace(int $workspaceId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find tag by ID.
     *
     * @param int $id
     * @return Tag|null
     */
    public function findById(int $id): ?Tag;

    /**
     * Create a new tag.
     *
     * @param array $data
     * @return Tag
     */
    public function create(array $data): Tag;

    /**
     * Update a tag.
     *
     * @param Tag $tag
     * @param array $data
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag;

    /**
     * Delete a tag.
     *
     * @param Tag $tag
     * @return bool
     */
    public function delete(Tag $tag): bool;

    /**
     * Find tags by task ID.
     *
     * @param int $taskId
     * @return Collection
     */
    public function findByTaskId(int $taskId): Collection;
}
