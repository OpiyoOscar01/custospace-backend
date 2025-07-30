<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        // Anyone can view comments unless they are internal comments
        if ($comment->is_internal) {
            // Check if user has permission to view internal comments
            // This is just an example, replace with your actual permission check
            return $user->hasPermissionTo('view internal comments');
        }
        
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Users can edit their own comments
        if ($user->id === $comment->user_id) {
            return true;
        }
        
        // Admins can edit any comment
        // This is just an example, replace with  actual permission check
        return $user->hasPermissionTo('edit comments');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Users can delete their own comments
        if ($user->id === $comment->user_id) {
            return true;
        }
        
        // Admins can delete any comment
        // This is just an example, replace with  actual permission check
        return $user->hasPermissionTo('delete comments');
    }

    /**
     * Determine whether the user can toggle internal status.
     */
    public function toggleInternal(User $user, Comment $comment): bool
    {
        // Only users with permission can toggle internal status
        // This is just an example, replace with  actual permission check
        return $user->hasPermissionTo('manage internal comments');
    }
}