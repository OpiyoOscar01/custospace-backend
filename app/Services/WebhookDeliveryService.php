<?php

namespace App\Services;

use App\Models\WebhookDelivery;
use App\Repositories\Contracts\WebhookDeliveryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WebhookDelivery Service
 * 
 * Handles webhook delivery business logic
 */
class WebhookDeliveryService
{
    public function __construct(
        private WebhookDeliveryRepositoryInterface $repository
    ) {}

    /**
     * Get webhook deliveries with filtering and pagination
     */
    public function getWebhookDeliveries(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    /**
     * Find webhook delivery by ID
     */
    public function findWebhookDelivery(int $id): ?WebhookDelivery
    {
        return $this->repository->findById($id);
    }

    /**
     * Create new webhook delivery
     */
    public function createWebhookDelivery(array $data): WebhookDelivery
    {
        return $this->repository->create($data);
    }

    /**
     * Update webhook delivery
     */
    public function updateWebhookDelivery(WebhookDelivery $webhookDelivery, array $data): WebhookDelivery
    {
        return $this->repository->update($webhookDelivery, $data);
    }

    /**
     * Delete webhook delivery
     */
    public function deleteWebhookDelivery(WebhookDelivery $webhookDelivery): bool
    {
        return $this->repository->delete($webhookDelivery);
    }

    /**
     * Retry failed webhook delivery
     */
    public function retryDelivery(WebhookDelivery $webhookDelivery): WebhookDelivery
    {
        if (!$webhookDelivery->isFailed()) {
            throw new \InvalidArgumentException('Only failed deliveries can be retried');
        }

        // Reset status to pending for retry
        $webhookDelivery = $this->repository->update($webhookDelivery, [
            'status' => WebhookDelivery::STATUS_PENDING,
            'next_attempt_at' => now(),
        ]);

        // Attempt delivery
        return $this->attemptDelivery($webhookDelivery);
    }

    /**
     * Process webhook delivery attempt
     */
    public function attemptDelivery(WebhookDelivery $webhookDelivery): WebhookDelivery
    {
        try {
            // Increment attempts
            $webhookDelivery = $this->repository->incrementAttempts($webhookDelivery);

            // Get webhook URL (assuming webhook model has url attribute)
            $webhookUrl = $webhookDelivery->webhook->url;

            // Make HTTP request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Laravel-Webhook-Client/1.0',
                ])
                ->post($webhookUrl, [
                    'event' => $webhookDelivery->event,
                    'payload' => $webhookDelivery->payload,
                    'delivery_id' => $webhookDelivery->id,
                    'timestamp' => $webhookDelivery->created_at->toISOString(),
                ]);

            // Handle response
            if ($response->successful()) {
                return $this->repository->markAsDelivered(
                    $webhookDelivery,
                    $response->status(),
                    $response->body()
                );
            } else {
                return $this->repository->markAsFailed(
                    $webhookDelivery,
                    $response->status(),
                    $response->body()
                );
            }
        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'delivery_id' => $webhookDelivery->id,
                'error' => $e->getMessage(),
            ]);

            return $this->repository->markAsFailed(
                $webhookDelivery,
                0,
                $e->getMessage()
            );
        }
    }

    /**
     * Get delivery statistics
     */
    public function getDeliveryStats(?int $webhookId = null): array
    {
        $query = WebhookDelivery::query();

        if ($webhookId) {
            $query->where('webhook_id', $webhookId);
        }

        $total = $query->count();
        $delivered = $query->where('status', WebhookDelivery::STATUS_DELIVERED)->count();
        $failed = $query->where('status', WebhookDelivery::STATUS_FAILED)->count();
        $pending = $query->where('status', WebhookDelivery::STATUS_PENDING)->count();

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Process failed deliveries for retry
     */
    public function processFailedDeliveries(): int
    {
        $failedDeliveries = $this->repository->getFailedReadyForRetry();
        $processedCount = 0;

        foreach ($failedDeliveries as $delivery) {
            $this->attemptDelivery($delivery);
            $processedCount++;
        }

        return $processedCount;
    }
}
