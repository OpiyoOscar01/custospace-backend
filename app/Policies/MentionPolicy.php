<?php

namespace App\Policies;

use App\Models\Mention;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MentionPolicy
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
    public function view(User $user, Mention $mention): bool
    {
        // Users can only view their own mentions
        return $user->id === $mention->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Mention $mention): bool
    {
        // Users can only mark their own mentions as read
        return $user->id === $mention->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Mention $mention): bool
    {
        // Users can only delete their own mentions
        return $user->id === $mention->user_id;
    }
}
