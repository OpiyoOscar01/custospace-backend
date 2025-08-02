<?php

namespace App\Repositories\Contracts;

use App\Models\WebhookDelivery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * WebhookDelivery Repository Interface
 * 
 * Defines contract for webhook delivery data access operations
 */
interface WebhookDeliveryRepositoryInterface
{
    /**
     * Get paginated webhook deliveries with optional filtering
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find webhook delivery by ID
     */
    public function findById(int $id): ?WebhookDelivery;

    /**
     * Create new webhook delivery
     */
    public function create(array $data): WebhookDelivery;

    /**
     * Update webhook delivery
     */
    public function update(WebhookDelivery $webhookDelivery, array $data): WebhookDelivery;

    /**
     * Delete webhook delivery
     */
    public function delete(WebhookDelivery $webhookDelivery): bool;

    /**
     * Get deliveries by webhook ID
     */
    public function getByWebhookId(int $webhookId): Collection;

    /**
     * Get deliveries by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get failed deliveries ready for retry
     */
    public function getFailedReadyForRetry(): Collection;

    /**
     * Mark delivery as delivered
     */
    public function markAsDelivered(WebhookDelivery $webhookDelivery, int $responseCode, string $responseBody): WebhookDelivery;

    /**
     * Mark delivery as failed
     */
    public function markAsFailed(WebhookDelivery $webhookDelivery, int $responseCode, string $responseBody): WebhookDelivery;

    /**
     * Increment delivery attempts
     */
    public function incrementAttempts(WebhookDelivery $webhookDelivery): WebhookDelivery;
}
