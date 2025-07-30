<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

/**
 * Authorization policy for Attachment model.
 */
class AttachmentPolicy
{
    /**
     * Determine whether the user can view any attachments.
     */
    public function viewAny(User $user): bool
    {
        return true; // Adjust based on your business logic
    }

    /**
     * Determine whether the user can view the attachment.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        // Users can view their own attachments
        return $user->id === $attachment->user_id;
    }

    /**
     * Determine whether the user can create attachments.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create attachments
    }

    /**
     * Determine whether the user can update the attachment.
     */
    public function update(User $user, Attachment $attachment): bool
    {
        // Users can update their own attachments
        return $user->id === $attachment->user_id;
    }

    /**
     * Determine whether the user can delete the attachment.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        // Users can delete their own attachments
        return $user->id === $attachment->user_id;
    }

    /**
     * Determine whether the user can restore the attachment.
     */
    public function restore(User $user, Attachment $attachment): bool
    {
        return $user->id === $attachment->user_id;
    }

    /**
     * Determine whether the user can permanently delete the attachment.
     */
    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return $user->id === $attachment->user_id;
    }
}
