<?php

namespace App\Services;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CommentService
{
    /**
     * @var CommentRepositoryInterface
     */
    protected $commentRepository;
    
    /**
     * CommentService constructor.
     * 
     * @param CommentRepositoryInterface $commentRepository
     */
    public function __construct(CommentRepositoryInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }
    
    /**
     * Get paginated comments.
     * 
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedComments(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getPaginatedComments($criteria, $perPage);
    }
    
    /**
     * Get all comments for a specific commentable entity.
     * 
     * @param string $commentableType
     * @param int $commentableId
     * @param bool $includeReplies
     * @return Collection
     */
    public function getCommentsByCommentable(string $commentableType, int $commentableId, bool $includeReplies = false): Collection
    {
        return $this->commentRepository->getCommentsByCommentable($commentableType, $commentableId, $includeReplies);
    }
    
    /**
     * Get a comment by ID.
     * 
     * @param int $id
     * @return Comment|null
     */
    public function getCommentById(int $id): ?Comment
    {
        return $this->commentRepository->getCommentById($id);
    }
    
    /**
     * Create a new comment.
     * 
     * @param array $data
     * @return Comment
     */
    public function createComment(array $data): Comment
    {
        return DB::transaction(function () use ($data) {
            $comment = $this->commentRepository->createComment($data);
            
            // Process mentions
            $this->processMentions($comment);
            
            return $comment->fresh();
        });
    }
    
    /**
     * Update an existing comment.
     * 
     * @param Comment $comment
     * @param array $data
     * @return Comment
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        return DB::transaction(function () use ($comment, $data) {
            $updatedComment = $this->commentRepository->updateComment($comment, $data);
            
            // Update mentions if content changed
            if (isset($data['content'])) {
                // Clear existing mentions
                $comment->mentions()->delete();
                
                // Process new mentions
                $this->processMentions($updatedComment);
            }
            
            return $updatedComment;
        });
    }
    
    /**
     * Delete a comment.
     * 
     * @param Comment $comment
     * @return bool
     */
    public function deleteComment(Comment $comment): bool
    {
        return DB::transaction(function () use ($comment) {
            // Delete all mentions associated with this comment
            $comment->mentions()->delete();
            
            // Delete the comment
            return $this->commentRepository->deleteComment($comment);
        });
    }
    
    /**
     * Process and create mentions from comment content.
     * 
     * @param Comment $comment
     * @return void
     */
    protected function processMentions(Comment $comment): void
    {
        // Simple regex to find @username mentions
        // To do: This has to be more sophisticated, you might want a more sophisticated approach
        preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->content, $matches);
        
        if (empty($matches[1])) {
            return;
        }
        
        $usernames = $matches[1];
        
        // Find mentioned users
        $users = \App\Models\User::whereIn('name', $usernames)->get();
        
        foreach ($users as $user) {
            $comment->mentions()->create([
                'user_id' => $user->id,
                'mentioned_by_id' => $comment->user_id,
            ]);
        }
    }
}