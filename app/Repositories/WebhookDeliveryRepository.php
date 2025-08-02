<?php

namespace App\Repositories;

use App\Models\WebhookDelivery;
use App\Repositories\Contracts\WebhookDeliveryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * WebhookDelivery Repository Implementation
 * 
 * Handles webhook delivery data access operations
 */
class WebhookDeliveryRepository implements WebhookDeliveryRepositoryInterface
{
    /**
     * Get paginated webhook deliveries with optional filtering
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WebhookDelivery::with(['webhook']);

        // Apply filters
        if (!empty($filters['webhook_id'])) {
            $query->where('webhook_id', $filters['webhook_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['event'])) {
            $query->where('event', 'like', '%' . $filters['event'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find webhook delivery by ID
     */
    public function findById(int $id): ?WebhookDelivery
    {
        return WebhookDelivery::with(['webhook'])->find($id);
    }

    /**
     * Create new webhook delivery
     */
    public function create(array $data): WebhookDelivery
    {
        return WebhookDelivery::create($data);
    }

    /**
     * Update webhook delivery
     */
    public function update(WebhookDelivery $webhookDelivery, array $data): WebhookDelivery
    {
        $webhookDelivery->update($data);
        return $webhookDelivery->fresh();
    }

    /**
     * Delete webhook delivery
     */
    public function delete(WebhookDelivery $webhookDelivery): bool
    {
        return $webhookDelivery->delete();
    }

    /**
     * Get deliveries by webhook ID
     */
    public function getByWebhookId(int $webhookId): Collection
    {
        return WebhookDelivery::where('webhook_id', $webhookId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get deliveries by status
     */
    public function getByStatus(string $status): Collection
    {
        return WebhookDelivery::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get failed deliveries ready for retry
     */
    public function getFailedReadyForRetry(): Collection
    {
        return WebhookDelivery::where('status', WebhookDelivery::STATUS_FAILED)
            ->where(function ($query) {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->where('attempts', '<', 5) // Max 5 attempts
            ->get();
    }

    /**
     * Mark delivery as delivered
     */
    public function markAsDelivered(WebhookDelivery $webhookDelivery, int $responseCode, string $responseBody): WebhookDelivery
    {
        return $this->update($webhookDelivery, [
            'status' => WebhookDelivery::STATUS_DELIVERED,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'next_attempt_at' => null,
        ]);
    }

    /**
     * Mark delivery as failed
     */
    public function markAsFailed(WebhookDelivery $webhookDelivery, int $responseCode, string $responseBody): WebhookDelivery
    {
        $nextAttempt = now()->addMinutes(pow(2, $webhookDelivery->attempts)); // Exponential backoff

        return $this->update($webhookDelivery, [
            'status' => WebhookDelivery::STATUS_FAILED,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'next_attempt_at' => $nextAttempt,
        ]);
    }

    /**
     * Increment delivery attempts
     */
    public function incrementAttempts(WebhookDelivery $webhookDelivery): WebhookDelivery
    {
        return $this->update($webhookDelivery, [
            'attempts' => $webhookDelivery->attempts + 1,
        ]);
    }
}
