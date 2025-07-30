<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConversationRequest;
use App\Http\Requests\UpdateConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var ConversationService
     */
    protected $conversationService;
    
    /**
     * ConversationController constructor.
     * 
     * @param ConversationService $conversationService
     */
    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
        $this->authorizeResource(Conversation::class, 'conversation', [
            'except' => ['index', 'store', 'markAsRead', 'createDirectConversation']
        ]);
    }
    
    /**
     * Display a listing of conversations.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Conversation::class);
        
        $filters = $request->validate([
            'workspace_id' => 'sometimes|exists:workspaces,id',
            'type' => 'sometimes|in:direct,group,channel',
        ]);
        
        $conversations = $this->conversationService->getConversationsForUser(Auth::id(), $filters);
        
        return ConversationResource::collection($conversations);
    }
    
    /**
     * Store a newly created conversation in storage.
     * 
     * @param CreateConversationRequest $request
     * @return ConversationResource
     */
    public function store(CreateConversationRequest $request): ConversationResource
    {
        $this->authorize('create', Conversation::class);
        
        $data = $request->validated();
        
        $conversation = $this->conversationService->createConversation($data);
        
        return new ConversationResource($conversation);
    }
    
    /**
     * Display the specified conversation.
     * 
     * @param Conversation $conversation
     * @return ConversationResource
     */
    public function show(Conversation $conversation): ConversationResource
    {
        return new ConversationResource($conversation->load(['users', 'messages']));
    }
    
    /**
     * Update the specified conversation in storage.
     * 
     * @param UpdateConversationRequest $request
     * @param Conversation $conversation
     * @return ConversationResource
     */
    public function update(UpdateConversationRequest $request, Conversation $conversation): ConversationResource
    {
        $updatedConversation = $this->conversationService->updateConversation($conversation, $request->validated());
        
        return new ConversationResource($updatedConversation);
    }
    
    /**
     * Remove the specified conversation from storage.
     * 
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function destroy(Conversation $conversation): JsonResponse
    {
        $this->conversationService->deleteConversation($conversation);
        
        return response()->json(null, 204);
    }
    
    /**
     * Add users to a conversation.
     * 
     * @param Request $request
     * @param Conversation $conversation
     * @return ConversationResource
     */
    public function addUsers(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorize('addUsers', $conversation);
        
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role' => 'sometimes|in:owner,admin,member',
        ]);
        
        $role = $validated['role'] ?? 'member';
        
        $this->conversationService->addUsers($conversation, $validated['user_ids'], $role);
        
        return new ConversationResource($conversation->fresh(['users']));
    }
    
    /**
     * Remove users from a conversation.
     * 
     * @param Request $request
     * @param Conversation $conversation
     * @return ConversationResource
     */
    public function removeUsers(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorize('removeUsers', $conversation);
        
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        
        $this->conversationService->removeUsers($conversation, $validated['user_ids']);
        
        return new ConversationResource($conversation->fresh(['users']));
    }
    
    /**
     * Update a user's role in the conversation.
     * 
     * @param Request $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function updateUserRole(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('updateUserRole', $conversation);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member',
        ]);
        
        $this->conversationService->updateUserRole(
            $conversation,
            $validated['user_id'],
            $validated['role']
        );
        
        return response()->json(['message' => 'User role updated successfully']);
    }
    
    /**
     * Mark conversation as read for the current user.
     * 
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        
        $this->conversationService->markAsRead($conversation, Auth::id());
        
        return response()->json(['message' => 'Conversation marked as read']);
    }
    
    /**
     * Create a direct conversation between the current user and another user.
     * 
     * @param Request $request
     * @return ConversationResource
     */
    public function createDirectConversation(Request $request): ConversationResource
    {
        $this->authorize('create', Conversation::class);
        
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'user_id' => 'required|exists:users,id',
        ]);
        
        $conversation = $this->conversationService->findOrCreateDirectConversation(
            $validated['workspace_id'],
            Auth::id(),
            $validated['user_id']
        );
        
        return new ConversationResource($conversation);
    }
}
