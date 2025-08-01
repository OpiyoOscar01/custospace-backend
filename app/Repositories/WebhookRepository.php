<?php

namespace App\Repositories;

use App\Models\Webhook;
use App\Repositories\Contracts\WebhookRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WebhookRepository implements WebhookRepositoryInterface
{
    /**
     * Get all webhooks with optional filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWebhooks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Webhook::with(['workspace']);

        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['event'])) {
            $query->whereJsonContains('events', $filters['event']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find a webhook by ID.
     *
     * @param int $id
     * @return Webhook|null
     */
    public function findById(int $id): ?Webhook
    {
        return Webhook::with(['workspace'])->find($id);
    }

    /**
     * Create a new webhook.
     *
     * @param array $data
     * @return Webhook
     */
    public function create(array $data): Webhook
    {
        return Webhook::create($data);
    }

    /**
     * Update a webhook.
     *
     * @param Webhook $webhook
     * @param array $data
     * @return Webhook
     */
    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->update($data);
        return $webhook->fresh(['workspace']);
    }

    /**
     * Delete a webhook.
     *
     * @param Webhook $webhook
     * @return bool
     */
    public function delete(Webhook $webhook): bool
    {
        return $webhook->delete();
    }

    /**
     * Get webhooks by workspace ID.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return Webhook::where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active webhooks that handle a specific event.
     *
     * @param string $event
     * @return Collection
     */
    public function getActiveWebhooksForEvent(string $event): Collection
    {
        return Webhook::active()
            ->whereJsonContains('events', $event)
            ->with(['workspace'])
            ->get();
    }

    /**
     * Toggle webhook active status.
     *
     * @param Webhook $webhook
     * @return Webhook
     */
    public function toggleActive(Webhook $webhook): Webhook
    {
        $webhook->update(['is_active' => !$webhook->is_active]);
        return $webhook->fresh();
    }
}
