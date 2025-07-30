<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MentionResource;
use App\Models\Mention;
use App\Services\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class MentionController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var MentionService
     */
    protected $mentionService;
    
    /**
     * MentionController constructor.
     * 
     * @param MentionService $mentionService
     */
    public function __construct(MentionService $mentionService)
    {
        $this->mentionService = $mentionService;
        $this->authorizeResource(Mention::class, 'mention', [
            'except' => ['index', 'markAllAsRead', 'getUnreadCount']
        ]);
    }
    
    /**
     * Display a listing of the mentions for the authenticated user.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $criteria = $request->validate([
            'is_read' => 'sometimes|boolean',
            'mentionable_type' => 'sometimes|string',
        ]);
        
        $perPage = $request->input('per_page', 15);
        
        $mentions = $this->mentionService->getPaginatedMentionsForUser(
            Auth::id(),
            $perPage,
            $criteria
        );
        
        return MentionResource::collection($mentions);
    }
    
    /**
     * Display the specified mention.
     * 
     * @param Mention $mention
     * @return MentionResource
     */
    public function show(Mention $mention): MentionResource
    {
        // Auto-mark as read when viewed
        $mention = $this->mentionService->markAsRead($mention);
        
        return new MentionResource($mention);
    }
    
    /**
     * Mark the specified mention as read.
     * 
     * @param Mention $mention
     * @return MentionResource
     */
    public function markAsRead(Mention $mention): MentionResource
    {
        $mention = $this->mentionService->markAsRead($mention);
        
        return new MentionResource($mention);
    }
    
    /**
     * Mark all mentions as read for the authenticated user.
     * 
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->mentionService->markAllAsRead(Auth::id());
        
        return response()->json([
            'message' => "{$count} mentions marked as read",
            'count' => $count,
        ]);
    }
    
    /**
     * Get the count of unread mentions for the authenticated user.
     * 
     * @return JsonResponse
     */
    public function getUnreadCount(): JsonResponse
    {
        $count = $this->mentionService->getUnreadMentionsCountForUser(Auth::id());
        
        return response()->json([
            'count' => $count,
        ]);
    }
    
    /**
     * Remove the specified mention from storage.
     * 
     * @param Mention $mention
     * @return JsonResponse
     */
    public function destroy(Mention $mention): JsonResponse
    {
        $this->mentionService->deleteMention($mention);
        
        return response()->json(null, 204);
    }
}
