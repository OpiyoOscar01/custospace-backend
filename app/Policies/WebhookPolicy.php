<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Auth\Access\Response;

class WebhookPolicy
{
    /**
     * Determine whether the user can view any webhooks.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view webhooks in their accessible workspaces
    }

    /**
     * Determine whether the user can view the webhook.
     */
    public function view(User $user, Webhook $webhook): bool
    {
        // Users can view webhooks in workspaces they have access to
        return $user->workspaces()->where('workspaces.id', $webhook->workspace_id)->exists();
    }

    /**
     * Determine whether the user can create webhooks.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create webhooks
    }

    /**
     * Determine whether the user can update the webhook.
     */
    public function update(User $user, Webhook $webhook): bool
    {
        // Only workspace admins can update webhooks
        return $user->workspaces()
                   ->where('workspaces.id', $webhook->workspace_id)
                   ->wherePivot('role', 'admin')
                   ->exists();
    }

    /**
     * Determine whether the user can delete the webhook.
     */
    public function delete(User $user, Webhook $webhook): bool
    {
        // Only workspace admins can delete webhooks
        return $user->workspaces()
                   ->where('workspaces.id', $webhook->workspace_id)
                   ->wherePivot('role', 'admin')
                   ->exists();
    }

    /**
     * Determine whether the user can restore the webhook.
     */
    public function restore(User $user, Webhook $webhook): bool
    {
        return $this->delete($user, $webhook);
    }

    /**
     * Determine whether the user can permanently delete the webhook.
     */
    public function forceDelete(User $user, Webhook $webhook): bool
    {
        return $this->delete($user, $webhook);
    }
}
