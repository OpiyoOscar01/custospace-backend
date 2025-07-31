<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WikiRevisionResource;
use App\Models\Wiki;
use App\Models\WikiRevision;
use App\Services\WikiRevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Wiki Revision API Controller - Handles HTTP requests for wiki revision operations
 * 
 * @group Wiki Revision Management
 */
class WikiRevisionController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private WikiRevisionService $revisionService
    ) {
    }

    /**
     * Get all revisions for a wiki.
     * 
     * @group Wiki Revision Management
     */
    public function index(Request $request, Wiki $wiki): AnonymousResourceCollection
    {
        $this->authorize('view', $wiki);

        $perPage = $request->integer('per_page', 20);
        $revisions = $this->revisionService->getWikiRevisions($wiki, $perPage);

        return WikiRevisionResource::collection($revisions);
    }

    /**
     * Get a specific revision.
     * 
     * @group Wiki Revision Management
     */
    public function show(Wiki $wiki, WikiRevision $revision): WikiRevisionResource
    {
        $this->authorize('view', $wiki);
        
        // Ensure revision belongs to wiki
        if ($revision->wiki_id !== $wiki->id) {
            abort(404, 'Revision not found for this wiki');
        }

        return WikiRevisionResource::make($revision->load(['user', 'wiki']));
    }

    /**
     * Restore wiki to a specific revision.
     * 
     * @group Wiki Revision Management
     */
    public function restore(Wiki $wiki, WikiRevision $revision): JsonResponse
    {
        $this->authorize('update', $wiki);

        if ($revision->wiki_id !== $wiki->id) {
            abort(404, 'Revision not found for this wiki');
        }

        $this->revisionService->restoreWikiToRevision($wiki, $revision);

        return response()->json([
            'message' => 'Wiki restored to revision successfully',
            'revision_id' => $revision->id
        ]);
    }

    /**
     * Compare two revisions.
     * 
     * @group Wiki Revision Management
     */
    public function compare(Request $request, Wiki $wiki): JsonResponse
    {
        $this->authorize('view', $wiki);

        $validated = $request->validate([
            'from_revision_id' => 'required|integer|exists:wiki_revisions,id',
            'to_revision_id' => 'required|integer|exists:wiki_revisions,id',
        ]);

        $comparison = $this->revisionService->compareRevisions(
            $validated['from_revision_id'],
            $validated['to_revision_id'],
            $wiki
        );

        return response()->json([
            'data' => $comparison
        ]);
    }

    /**
     * Delete a specific revision.
     * 
     * @group Wiki Revision Management
     */
    public function destroy(Wiki $wiki, WikiRevision $revision): JsonResponse
    {
        $this->authorize('update', $wiki);

        if ($revision->wiki_id !== $wiki->id) {
            abort(404, 'Revision not found for this wiki');
        }

        $this->revisionService->deleteRevision($revision);

        return response()->json([
            'message' => 'Revision deleted successfully'
        ], 204);
    }

    /**
     * Get revision statistics for a wiki.
     * 
     * @group Wiki Revision Management
     */
    public function statistics(Wiki $wiki): JsonResponse
    {
        $this->authorize('view', $wiki);

        $stats = $this->revisionService->getRevisionStatistics($wiki);

        return response()->json([
            'data' => $stats
        ]);
    }
}
