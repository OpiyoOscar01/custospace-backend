<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TagController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var TagService
     */
    protected TagService $tagService;

    /**
     * TagController constructor.
     *
     * @param TagService $tagService
     */
    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
        $this->authorizeResource(Tag::class, 'tag');
    }

    /**
     * Display a listing of the tags.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaceId = $request->query('workspace_id');
        $perPage = $request->query('per_page', 15);

        $tags = $this->tagService->getPaginatedByWorkspace($workspaceId, $perPage);

        return TagResource::collection($tags);
    }

    /**
     * Store a newly created tag in storage.
     *
     * @param CreateTagRequest $request
     * @return TagResource
     */
    public function store(CreateTagRequest $request): TagResource
    {
        $tag = $this->tagService->create($request->validated());

        return new TagResource($tag);
    }

    /**
     * Display the specified tag.
     *
     * @param Tag $tag
     * @return TagResource
     */
    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    /**
     * Update the specified tag in storage.
     *
     * @param UpdateTagRequest $request
     * @param Tag $tag
     * @return TagResource
     */
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $updatedTag = $this->tagService->update($tag, $request->validated());

        return new TagResource($updatedTag);
    }

    /**
     * Remove the specified tag from storage.
     *
     * @param Tag $tag
     * @return JsonResponse
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->tagService->delete($tag);

        return response()->json(null, 204);
    }

    /**
     * Assign tag to a task.
     *
     * @param Request $request
     * @param Tag $tag
     * @return JsonResponse
     */
    public function assignToTask(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);
        
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        $this->tagService->assignToTask($tag, $validated['task_id']);

        return response()->json(['message' => 'Tag assigned successfully']);
    }

    /**
     * Remove tag from a task.
     *
     * @param Request $request
     * @param Tag $tag
     * @return JsonResponse
     */
    public function removeFromTask(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);
        
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        $this->tagService->removeFromTask($tag, $validated['task_id']);

        return response()->json(['message' => 'Tag removed successfully']);
    }

    /**
     * Get all tags for a specific task.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getByTask(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        $tags = $this->tagService->findByTaskId($validated['task_id']);

        return TagResource::collection($tags);
    }
}
