<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWikiRequest;
use App\Http\Requests\UpdateWikiRequest;
use App\Http\Resources\WikiResource;
use App\Models\Wiki;
use App\Models\User;
use App\Services\WikiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Wiki API Controller - Handles HTTP requests for wiki operations
 * 
 * @group Wiki Management
 */
class WikiController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private WikiService $wikiService
    ) {
        // Apply policies to protect routes
        $this->authorizeResource(Wiki::class, 'wiki');
    }

    /**
     * Display a listing of wikis.
     * 
     * @group Wiki Management
     * @queryParam workspace_id integer Filter by workspace ID
     * @queryParam is_published boolean Filter by publication status
     * @queryParam parent_id integer Filter by parent wiki ID
     * @queryParam search string Search in title and content
     * @queryParam created_by_id integer Filter by creator
     * @queryParam per_page integer Number of items per page (default: 15)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'workspace_id',
            'is_published',
            'parent_id',
            'search',
            'created_by_id'
        ]);

        $perPage = $request->integer('per_page', 15);
        $wikis = $this->wikiService->getPaginatedWikis($filters, $perPage);

        return WikiResource::collection($wikis);
    }

    /**
     * Store a newly created wiki.
     * 
     * @group Wiki Management
     */
    public function store(CreateWikiRequest $request): JsonResponse
    {
        $wiki = $this->wikiService->createWiki($request->getValidatedData());

        return WikiResource::make($wiki)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified wiki.
     * 
     * @group Wiki Management
     */
    public function show(Wiki $wiki): WikiResource
    {
        $wiki = $this->wikiService->getWikiById($wiki->id);

        if (!$wiki) {
            abort(404, 'Wiki not found');
        }

        return WikiResource::make($wiki);
    }

    /**
     * Update the specified wiki.
     * 
     * @group Wiki Management
     */
    public function update(UpdateWikiRequest $request, Wiki $wiki): WikiResource
    {
        $this->wikiService->updateWiki($wiki, $request->validated());

        return WikiResource::make($wiki->fresh());
    }

    /**
     * Remove the specified wiki.
     * 
     * @group Wiki Management
     */
    public function destroy(Wiki $wiki): JsonResponse
    {
        $this->wikiService->deleteWiki($wiki);

        return response()->json([
            'message' => 'Wiki deleted successfully'
        ], 204);
    }

    /**
     * Toggle wiki publication status.
     * 
     * @group Wiki Management
     */
    public function togglePublication(Wiki $wiki): JsonResponse
    {
        $this->authorize('update', $wiki);

        $this->wikiService->toggleWikiPublication($wiki);

        return response()->json([
            'message' => 'Wiki publication status toggled successfully',
            'is_published' => $wiki->fresh()->is_published
        ]);
    }

    /**
     * Get wiki tree structure for workspace.
     * 
     * @group Wiki Management
     * @queryParam workspace_id integer required The workspace ID
     */
    public function tree(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id'
        ]);

        $tree = $this->wikiService->getWikiTree($request->integer('workspace_id'));

        return response()->json([
            'data' => WikiResource::collection($tree)
        ]);
    }

    /**
     * Search wikis within workspace.
     * 
     * @group Wiki Management
     * @queryParam workspace_id integer required The workspace ID
     * @queryParam q string required Search query
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id',
            'q' => 'required|string|min:2'
        ]);

        $results = $this->wikiService->searchWikis(
            $request->integer('workspace_id'),
            $request->string('q')
        );

        return response()->json([
            'data' => WikiResource::collection($results)
        ]);
    }

    /**
     * Get wiki breadcrumb navigation.
     * 
     * @group Wiki Management
     */
    public function breadcrumb(Wiki $wiki): JsonResponse
    {
        $this->authorize('view', $wiki);

        $breadcrumb = $this->wikiService->getWikiBreadcrumb($wiki);

        return response()->json([
            'data' => $breadcrumb
        ]);
    }

    /**
     * Assign user to wiki for collaboration.
     * 
     * @group Wiki Management
     */
    public function assignUser(Request $request, Wiki $wiki): JsonResponse
    {
        $this->authorize('update', $wiki);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'sometimes|string|in:viewer,collaborator,editor'
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = $validated['role'] ?? 'collaborator';

        $this->wikiService->assignUserToWiki($wiki, $user, $role);

        return response()->json([
            'message' => 'User assigned to wiki successfully'
        ]);
    }

    /**
     * Get wiki by slug within workspace.
     * 
     * @group Wiki Management
     */
    public function findBySlug(Request $request): WikiResource
    {
        $validated = $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id',
            'slug' => 'required|string'
        ]);

        $wiki = $this->wikiService->getWikiBySlug(
            $validated['workspace_id'],
            $validated['slug']
        );

        if (!$wiki) {
            abort(404, 'Wiki not found');
        }

        $this->authorize('view', $wiki);

        return WikiResource::make($wiki);
    }

    /**
     * Duplicate wiki.
     * 
     * @group Wiki Management
     */
    public function duplicate(Request $request, Wiki $wiki): JsonResponse
    {
        $this->authorize('create', Wiki::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'workspace_id' => 'sometimes|integer|exists:workspaces,id'
        ]);

        $data = [
            'workspace_id' => $validated['workspace_id'] ?? $wiki->workspace_id,
            'created_by_id' => \Auth::id(),
            'parent_id' => $wiki->parent_id,
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? \Str::slug($validated['title']),
            'content' => $wiki->content,
            'is_published' => false,
            'metadata' => $wiki->metadata,
            'revision_summary' => 'Duplicated from: ' . $wiki->title,
        ];

        $duplicatedWiki = $this->wikiService->createWiki($data);

        return WikiResource::make($duplicatedWiki)
            ->response()
            ->setStatusCode(201);
    }
}
