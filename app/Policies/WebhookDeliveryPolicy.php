<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookDelivery;

/**
 * WebhookDelivery Policy
 * 
 * Defines authorization rules for webhook delivery operations
 */
class WebhookDeliveryPolicy
{
    /**
     * Determine whether the user can view any webhook deliveries.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.view');
    }

    /**
     * Determine whether the user can view the webhook delivery.
     */
    public function view(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.view') && 
               $this->canAccessWebhook($user, $webhookDelivery);
    }

    /**
     * Determine whether the user can create webhook deliveries.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.create');
    }

    /**
     * Determine whether the user can update the webhook delivery.
     */
    public function update(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.update') && 
               $this->canAccessWebhook($user, $webhookDelivery);
    }

    /**
     * Determine whether the user can delete the webhook delivery.
     */
    public function delete(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.delete') && 
               $this->canAccessWebhook($user, $webhookDelivery);
    }

    /**
     * Determine whether the user can retry the webhook delivery.
     */
    public function retry(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.retry') && 
               $this->canAccessWebhook($user, $webhookDelivery) &&
               $webhookDelivery->isFailed();
    }

    /**
     * Determine whether the user can view response details.
     */
    public function viewResponse(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.view_response') && 
               $this->canAccessWebhook($user, $webhookDelivery);
    }

    /**
     * Determine whether the user can process failed deliveries.
     */
    public function processFailedDeliveries(User $user): bool
    {
        return $user->hasPermissionTo('webhook_deliveries.process_failed');
    }

    /**
     * Check if user can access the webhook associated with the delivery
     */
    private function canAccessWebhook(User $user, WebhookDelivery $webhookDelivery): bool
    {
        // If user is admin, allow access
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user belongs to the same workspace as the webhook
        if ($webhookDelivery->webhook && $webhookDelivery->webhook->workspace_id) {
            return $user->workspaces()->where('workspace_id', $webhookDelivery->webhook->workspace_id)->exists();
        }

        // Default to allowing access for now (adjust based on your business logic)
        return true;
    }
}
