<?php

namespace App\Repositories\Contracts;

use App\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for Media Repository
 */
interface MediaRepositoryInterface
{
    /**
     * Get all media with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find media by ID.
     */
    public function findById(int $id): ?Media;

    /**
     * Create a new media.
     */
    public function create(array $data): Media;

    /**
     * Update an existing media.
     */
    public function update(Media $media, array $data): bool;

    /**
     * Delete a media.
     */
    public function delete(Media $media): bool;

    /**
     * Get media by workspace.
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get media by collection.
     */
    public function getByCollection(string $collection, int $workspaceId = null): Collection;

    /**
     * Get media by user.
     */
    public function getByUser(int $userId): Collection;
}
