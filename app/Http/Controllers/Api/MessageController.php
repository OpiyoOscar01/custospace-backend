<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var MessageService
     */
    protected $messageService;
    
    /**
     * @var ConversationService
     */
    protected $conversationService;
    
    /**
     * MessageController constructor.
     * 
     * @param MessageService $messageService
     * @param ConversationService $conversationService
     */
    public function __construct(
        MessageService $messageService, 
        ConversationService $conversationService
    ) {
        $this->messageService = $messageService;
        $this->conversationService = $conversationService;
        $this->authorizeResource(Message::class, 'message', [
            'except' => ['index', 'getMessagesAfter', 'store']
        ]);
    }
    
    /**
     * Display a listing of messages for a conversation.
     * 
     * @param Request $request
     * @param Conversation $conversation
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $this->authorize('view', $conversation);
        
        $perPage = $request->input('per_page', 50);
        
        $criteria = $request->validate([
            'type' => 'sometimes|in:text,file,image,system',
            'user_id' => 'sometimes|exists:users,id',
        ]);
        
        $messages = $this->messageService->getPaginatedMessages(
            $conversation->id,
            $perPage,
            $criteria
        );
        
        // Mark conversation as read for current user
        $this->conversationService->markAsRead($conversation, Auth::id());
        
        return MessageResource::collection($messages);
    }
    
    /**
     * Get messages after a specific timestamp.
     * 
     * @param Request $request
     * @param Conversation $conversation
     * @return AnonymousResourceCollection
     */
    public function getMessagesAfter(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $this->authorize('view', $conversation);
        
        $validated = $request->validate([
            'timestamp' => 'required|date_format:Y-m-d H:i:s',
        ]);
        
        $messages = $this->messageService->getMessagesAfter(
            $conversation->id,
            $validated['timestamp']
        );
        
        return MessageResource::collection($messages);
    }
    
    /**
     * Store a newly created message in storage.
     * 
     * @param CreateMessageRequest $request
     * @return MessageResource
     */
    public function store(CreateMessageRequest $request): MessageResource
    {
        $data = $request->validated();
        
        // Check if user has access to this conversation
        $conversation = Conversation::findOrFail($data['conversation_id']);
        $this->authorize('sendMessage', $conversation);
        
        // Add user_id
        $data['user_id'] = Auth::id();
        
        $message = $this->messageService->createMessage($data);
        
        return new MessageResource($message);
    }
    
    /**
     * Display the specified message.
     * 
     * @param Message $message
     * @return MessageResource
     */
    public function show(Message $message): MessageResource
    {
        return new MessageResource($message->load('user'));
    }
    
    /**
     * Update the specified message in storage.
     * 
     * @param UpdateMessageRequest $request
     * @param Message $message
     * @return MessageResource
     */
    public function update(UpdateMessageRequest $request, Message $message): MessageResource
    {
        $updatedMessage = $this->messageService->updateMessage($message, $request->validated());
        
        return new MessageResource($updatedMessage);
    }
    
    /**
     * Remove the specified message from storage.
     * 
     * @param Message $message
     * @return JsonResponse
     */
    public function destroy(Message $message): JsonResponse
    {
        $this->messageService->deleteMessage($message);
        
        return response()->json(null, 204);
    }
}
