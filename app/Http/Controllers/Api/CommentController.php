<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var CommentService
     */
    protected $commentService;
    
    /**
     * CommentController constructor.
     * 
     * @param CommentService $commentService
     */
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
        $this->authorizeResource(Comment::class, 'comment', [
            'except' => ['index', 'getByCommentable']
        ]);
    }
    
    /**
     * Display a listing of comments.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $criteria = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'commentable_type' => 'sometimes|string',
            'commentable_id' => 'sometimes|required_with:commentable_type|integer',
            'parent_id' => 'sometimes|nullable|exists:comments,id',
        ]);
        
        $perPage = $request->input('per_page', 15);
        
        $comments = $this->commentService->getPaginatedComments($criteria, $perPage);
        
        return CommentResource::collection($comments);
    }
    
    /**
     * Get comments by commentable entity.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getByCommentable(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'include_replies' => 'sometimes|boolean',
        ]);
        
        $includeReplies = $request->input('include_replies', false);
        
        $comments = $this->commentService->getCommentsByCommentable(
            $validated['commentable_type'],
            $validated['commentable_id'],
            $includeReplies
        );
        
        return CommentResource::collection($comments);
    }
    
    /**
     * Store a newly created comment in storage.
     * 
     * @param CreateCommentRequest $request
     * @return CommentResource
     */
    public function store(CreateCommentRequest $request): CommentResource
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        
        $comment = $this->commentService->createComment($data);
        
        return new CommentResource($comment);
    }
    
    /**
     * Display the specified comment.
     * 
     * @param Comment $comment
     * @return CommentResource
     */
    public function show(Comment $comment): CommentResource
    {
        return new CommentResource($comment->load(['user', 'replies.user']));
    }
    
    /**
     * Update the specified comment in storage.
     * 
     * @param UpdateCommentRequest $request
     * @param Comment $comment
     * @return CommentResource
     */
    public function update(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        $updatedComment = $this->commentService->updateComment($comment, $request->validated());
        
        return new CommentResource($updatedComment);
    }
    
    /**
     * Remove the specified comment from storage.
     * 
     * @param Comment $comment
     * @return JsonResponse
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->commentService->deleteComment($comment);
        
        return response()->json(null, 204);
    }
    
    /**
     * Toggle internal status of a comment.
     * 
     * @param Comment $comment
     * @return CommentResource
     */
    public function toggleInternal(Comment $comment): CommentResource
    {
        $this->authorize('update', $comment);
        
        $updatedComment = $this->commentService->updateComment($comment, [
            'is_internal' => !$comment->is_internal,
        ]);
        
        return new CommentResource($updatedComment);
    }
}