<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

/**
 * Authorization policy for Media model.
 */
class MediaPolicy
{
    /**
     * Determine whether the user can view any media.
     */
    public function viewAny(User $user): bool
    {
        return true; // Adjust based on your business logic
    }

    /**
     * Determine whether the user can view the media.
     */
    public function view(User $user, Media $media): bool
    {
        // Users can view media in their workspace or their own media
        return $user->id === $media->user_id || 
               $user->workspaces()->where('id', $media->workspace_id)->exists();
    }

    /**
     * Determine whether the user can create media.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create media
    }

    /**
     * Determine whether the user can update the media.
     */
    public function update(User $user, Media $media): bool
    {
        // Users can update media in their workspace or their own media
        return $user->id === $media->user_id || 
               $user->workspaces()->where('id', $media->workspace_id)->exists();
    }

    /**
     * Determine whether the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        // Users can delete their own media or if they have workspace access
        return $user->id === $media->user_id || 
               $user->workspaces()->where('id', $media->workspace_id)->exists();
    }

    /**
     * Determine whether the user can restore the media.
     */
    public function restore(User $user, Media $media): bool
    {
        return $user->id === $media->user_id || 
               $user->workspaces()->where('id', $media->workspace_id)->exists();
    }

    /**
     * Determine whether the user can permanently delete the media.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        return $user->id === $media->user_id || 
               $user->workspaces()->where('id', $media->workspace_id)->exists();
    }
}
