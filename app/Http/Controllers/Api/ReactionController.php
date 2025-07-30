<?php
// app/Http/Controllers/Api/ReactionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReactionRequest;
use App\Http\Requests\UpdateReactionRequest;
use App\Http\Resources\ReactionResource;
use App\Models\Reaction;
use App\Services\ReactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Reaction API Controller
 * 
 * Handles HTTP requests for reaction operations
 */
class ReactionController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ReactionService $reactionService
    ) {
        // Apply authorization policies
        $this->authorizeResource(Reaction::class, 'reaction');
    }

    /**
     * Display a listing of reactions.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'user_id',
            'type',
            'reactable_type',
            'reactable_id'
        ]);

        $perPage = min($request->get('per_page', 15), 100);
        $reactions = $this->reactionService->getPaginatedReactions($filters, $perPage);

        return ReactionResource::collection($reactions);
    }

    /**
     * Store a newly created reaction.
     * 
     * @param CreateReactionRequest $request
     * @return ReactionResource
     */
    public function store(CreateReactionRequest $request): ReactionResource
    {
        $reaction = $this->reactionService->createReaction($request->validated());

        return new ReactionResource($reaction);
    }

    /**
     * Display the specified reaction.
     * 
     * @param Reaction $reaction
     * @return ReactionResource
     */
    public function show(Reaction $reaction): ReactionResource
    {
        return new ReactionResource($reaction->load(['user', 'reactable']));
    }

    /**
     * Update the specified reaction.
     * 
     * @param UpdateReactionRequest $request
     * @param Reaction $reaction
     * @return ReactionResource
     */
    public function update(UpdateReactionRequest $request, Reaction $reaction): ReactionResource
    {
        $reaction = $this->reactionService->updateReaction($reaction, $request->validated());

        return new ReactionResource($reaction);
    }

    /**
     * Remove the specified reaction.
     * 
     * @param Reaction $reaction
     * @return JsonResponse
     */
    public function destroy(Reaction $reaction): JsonResponse
    {
        $this->reactionService->deleteReaction($reaction);

        return response()->json([
            'message' => 'Reaction deleted successfully.'
        ]);
    }

    /**
     * Toggle a reaction for the authenticated user.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'reactable_type' => ['required', 'string'],
            'reactable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:' . implode(',', Reaction::TYPES)],
        ]);

        $reaction = $this->reactionService->toggleReaction(
            $request->reactable_type,
            $request->reactable_id,
            $request->type
        );

        if ($reaction) {
            return response()->json([
                'message' => 'Reaction added successfully.',
                'data' => new ReactionResource($reaction),
                'action' => 'added'
            ]);
        } else {
            return response()->json([
                'message' => 'Reaction removed successfully.',
                'data' => null,
                'action' => 'removed'
            ]);
        }
    }

    /**
     * Get reactions for a specific item.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getItemReactions(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'reactable_type' => ['required', 'string'],
            'reactable_id' => ['required', 'integer'],
        ]);

        $reactions = $this->reactionService->getItemReactions(
            $request->reactable_type,
            $request->reactable_id
        );

        return ReactionResource::collection($reactions);
    }

    /**
     * Get reaction summary for a specific item.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReactionSummary(Request $request): JsonResponse
    {
        $request->validate([
            'reactable_type' => ['required', 'string'],
            'reactable_id' => ['required', 'integer'],
        ]);

        $summary = $this->reactionService->getReactionSummary(
            $request->reactable_type,
            $request->reactable_id,
            \Auth::id()
        );

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Get user's reactions.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getUserReactions(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['type', 'reactable_type', 'limit']);
        $reactions = $this->reactionService->getUserReactions(\Auth::id(), $filters);

        return ReactionResource::collection($reactions);
    }

    /**
     * Bulk toggle reactions for multiple items.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkToggle(Request $request): JsonResponse
    {
        $this->authorize('create', Reaction::class);

        $request->validate([
            'items' => ['required', 'array', 'max:50'],
            'items.*.reactable_type' => ['required', 'string'],
            'items.*.reactable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:' . implode(',', Reaction::TYPES)],
        ]);

        $results = $this->reactionService->bulkToggleReactions(
            $request->items,
            $request->type
        );

        return response()->json([
            'message' => 'Bulk reaction toggle completed successfully.',
            'data' => $results
        ]);
    }

    /**
     * Get available reaction types.
     * 
     * @return JsonResponse
     */
    public function getAvailableTypes(): JsonResponse
    {
        $types = $this->reactionService->getAvailableTypes();

        return response()->json([
            'data' => $types
        ]);
    }
}