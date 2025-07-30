<?php

namespace App\Services;

use App\Models\Media;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * Service class for handling media business logic.
 */
class MediaService
{
    /**
     * Constructor to inject repository dependency.
     */
    public function __construct(
        private MediaRepositoryInterface $mediaRepository
    ) {}

    /**
     * Get all media with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->mediaRepository->getAllPaginated($perPage);
    }

    /**
     * Find media by ID.
     */
    public function findById(int $id): ?Media
    {
        return $this->mediaRepository->findById($id);
    }

    /**
     * Create a new media.
     */
    public function create(array $data): Media
    {
        // Add user_id from authenticated user
        $data['user_id'] = \Auth::id();
        
        return $this->mediaRepository->create($data);
    }

    /**
     * Update an existing media.
     */
    public function update(Media $media, array $data): bool
    {
        return $this->mediaRepository->update($media, $data);
    }

    /**
     * Delete a media.
     */
    public function delete(Media $media): bool
    {
        return $this->mediaRepository->delete($media);
    }

    /**
     * Move media to a different collection.
     */
    public function moveToCollection(Media $media, string $collection): bool
    {
        return $this->mediaRepository->update($media, [
            'collection' => $collection
        ]);
    }

    /**
     * Update media metadata.
     */
    public function updateMetadata(Media $media, array $metadata): bool
    {
        $currentMetadata = $media->metadata ?: [];
        $newMetadata = array_merge($currentMetadata, $metadata);

        return $this->mediaRepository->update($media, [
            'metadata' => $newMetadata
        ]);
    }

    /**
     * Duplicate a media file.
     */
    public function duplicate(Media $media, array $overrides = []): Media
    {
        $originalPath = $media->path;
        $pathInfo = pathinfo($originalPath);
        $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_copy.' . $pathInfo['extension'];

        // Copy the physical file
        Storage::disk($media->disk)->copy($originalPath, $newPath);

        // Prepare data for new media
        $newMediaData = array_merge([
            'workspace_id' => $media->workspace_id,
            'user_id' => \Auth::id(),
            'name' => $media->name . ' (Copy)',
            'original_name' => $media->original_name,
            'path' => $newPath,
            'disk' => $media->disk,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'collection' => $media->collection,
            'metadata' => $media->metadata,
        ], $overrides);

        return $this->mediaRepository->create($newMediaData);
    }

    /**
     * Get media by workspace.
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return $this->mediaRepository->getByWorkspace($workspaceId);
    }

    /**
     * Get media by collection.
     */
    public function getByCollection(string $collection, ?int $workspaceId = null): Collection
    {
        return $this->mediaRepository->getByCollection($collection, $workspaceId);
    }
}
