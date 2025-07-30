<?php

namespace App\Repositories\Contracts;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepositoryInterface
{
    /**
     * Get paginated comments.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedComments(array $criteria, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Get all comments for a specific commentable entity.
     *
     * @param string $commentableType
     * @param int $commentableId
     * @param bool $includeReplies
     * @return Collection
     */
    public function getCommentsByCommentable(string $commentableType, int $commentableId, bool $includeReplies = false): Collection;
    
    /**
     * Get a comment by ID.
     *
     * @param int $id
     * @return Comment|null
     */
    public function getCommentById(int $id): ?Comment;
    
    /**
     * Create a new comment.
     *
     * @param array $data
     * @return Comment
     */
    public function createComment(array $data): Comment;
    
    /**
     * Update an existing comment.
     *
     * @param Comment $comment
     * @param array $data
     * @return Comment
     */
    public function updateComment(Comment $comment, array $data): Comment;
    
    /**
     * Delete a comment.
     *
     * @param Comment $comment
     * @return bool
     */
    public function deleteComment(Comment $comment): bool;
}