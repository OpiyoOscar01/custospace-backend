<?php

namespace App\Repositories;

use App\Models\Media;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Media Repository Implementation
 */
class MediaRepository implements MediaRepositoryInterface
{
    /**
     * Get all media with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Media::with(['workspace', 'user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find media by ID.
     */
    public function findById(int $id): ?Media
    {
        return Media::with(['workspace', 'user'])->find($id);
    }

    /**
     * Create a new media.
     */
    public function create(array $data): Media
    {
        return Media::create($data);
    }

    /**
     * Update an existing media.
     */
    public function update(Media $media, array $data): bool
    {
        return $media->update($data);
    }

    /**
     * Delete a media.
     */
    public function delete(Media $media): bool
    {
        // Delete the physical file
        \Storage::disk($media->disk)->delete($media->path);
        
        return $media->delete();
    }

    /**
     * Get media by workspace.
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return Media::where('workspace_id', $workspaceId)
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * Get media by collection.
     */
    public function getByCollection(string $collection, int $workspaceId = null): Collection
    {
        $query = Media::where('collection', $collection)
            ->with(['workspace', 'user']);

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->latest()->get();
    }

    /**
     * Get media by user.
     */
    public function getByUser(int $userId): Collection
    {
        return Media::where('user_id', $userId)
            ->with('workspace')
            ->latest()
            ->get();
    }
}
