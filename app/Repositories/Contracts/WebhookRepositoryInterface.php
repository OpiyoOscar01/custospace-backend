<?php

namespace App\Repositories\Contracts;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookRepositoryInterface
{
    /**
     * Get all webhooks with optional filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWebhooks(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a webhook by ID.
     *
     * @param int $id
     * @return Webhook|null
     */
    public function findById(int $id): ?Webhook;

    /**
     * Create a new webhook.
     *
     * @param array $data
     * @return Webhook
     */
    public function create(array $data): Webhook;

    /**
     * Update a webhook.
     *
     * @param Webhook $webhook
     * @param array $data
     * @return Webhook
     */
    public function update(Webhook $webhook, array $data): Webhook;

    /**
     * Delete a webhook.
     *
     * @param Webhook $webhook
     * @return bool
     */
    public function delete(Webhook $webhook): bool;

    /**
     * Get webhooks by workspace ID.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get active webhooks that handle a specific event.
     *
     * @param string $event
     * @return Collection
     */
    public function getActiveWebhooksForEvent(string $event): Collection;

    /**
     * Toggle webhook active status.
     *
     * @param Webhook $webhook
     * @return Webhook
     */
    public function toggleActive(Webhook $webhook): Webhook;
}
