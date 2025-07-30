<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * API Controller for managing media.
 */
class MediaController extends Controller
{
    use AuthorizesRequests;
    /**
     * Constructor to inject service dependency.
     */
    public function __construct(
        private MediaService $mediaService
    ) {
        $this->authorizeResource(Media::class, 'media');
    }

    /**
     * Display a listing of media.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $media = $this->mediaService->getAllPaginated($perPage);

        return MediaResource::collection($media);
    }

    /**
     * Store a newly created media.
     */
    public function store(CreateMediaRequest $request): MediaResource
    {
        $media = $this->mediaService->create($request->validated());

        return new MediaResource($media);
    }

    /**
     * Display the specified media.
     */
    public function show(Media $media): MediaResource
    {
        return new MediaResource($media->load(['workspace', 'user']));
    }

    /**
     * Update the specified media.
     */
    public function update(UpdateMediaRequest $request, Media $media): MediaResource
    {
        $this->mediaService->update($media, $request->validated());

        return new MediaResource($media->fresh(['workspace', 'user']));
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

        return response()->json(['message' => 'Media deleted successfully.']);
    }

    /**
     * Move media to a different collection.
     */
    public function moveToCollection(Request $request, Media $media): MediaResource
    {
        $this->authorize('update', $media);

        $request->validate([
            'collection' => 'required|string|max:255'
        ]);

        $this->mediaService->moveToCollection($media, $request->input('collection'));

        return new MediaResource($media->fresh(['workspace', 'user']));
    }

    /**
     * Update media metadata.
     */
    public function updateMetadata(Request $request, Media $media): MediaResource
    {
        $this->authorize('update', $media);

        $request->validate([
            'metadata' => 'required|array'
        ]);

        $this->mediaService->updateMetadata($media, $request->input('metadata'));

        return new MediaResource($media->fresh(['workspace', 'user']));
    }

    /**
     * Duplicate a media file.
     */
    public function duplicate(Request $request, Media $media): MediaResource
    {
        $this->authorize('view', $media);

        $overrides = $request->validate([
            'name' => 'sometimes|string|max:255',
            'collection' => 'sometimes|string|max:255'
        ]);

        $duplicatedMedia = $this->mediaService->duplicate($media, $overrides);

        return new MediaResource($duplicatedMedia->load(['workspace', 'user']));
    }
}
