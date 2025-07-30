<?php
namespace App\Repositories;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getPaginatedComments(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = Comment::query();
        
        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }
        
        if (isset($criteria['commentable_type']) && isset($criteria['commentable_id'])) {
            $query->where('commentable_type', $criteria['commentable_type'])
                 ->where('commentable_id', $criteria['commentable_id']);
        }
        
        if (isset($criteria['parent_id'])) {
            $query->where('parent_id', $criteria['parent_id']);
        } else {
            // If not looking for replies, get only top-level comments
            $query->whereNull('parent_id');
        }
        
        return $query->with(['user', 'replies.user'])
                    ->latest()
                    ->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getCommentsByCommentable(string $commentableType, int $commentableId, bool $includeReplies = false): Collection
    {
        $query = Comment::query()
            ->where('commentable_type', $commentableType)
            ->where('commentable_id', $commentableId);

        if (!$includeReplies) {
            $query->whereNull('parent_id');
        }

        return $query->with(['user', 'replies.user'])
                    ->latest()
                    ->get();
    }

    /**
     * @inheritDoc
     */
    public function getCommentById(int $id): ?Comment
    {
        return Comment::with(['user', 'replies.user'])->find($id);
    }

    /**
     * @inheritDoc
     */
    public function createComment(array $data): Comment
    {
        // Add user_id from authenticated user if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }
        
        return Comment::create($data);
    }

    /**
     * @inheritDoc
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        // Set edited flags
        $data['is_edited'] = true;
        $data['edited_at'] = Carbon::now();
        
        $comment->update($data);
        
        return $comment->fresh();
    }

    /**
     * @inheritDoc
     */
    public function deleteComment(Comment $comment): bool
    {
        return $comment->delete();
    }
}