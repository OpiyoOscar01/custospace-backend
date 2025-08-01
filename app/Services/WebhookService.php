<?php

namespace App\Services;

use App\Models\Webhook;
use App\Repositories\Contracts\WebhookRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Create a new WebhookService instance.
     */
    public function __construct(
        private WebhookRepositoryInterface $webhookRepository
    ) {}

    /**
     * Get all webhooks with filtering and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWebhooks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->webhookRepository->getAllWebhooks($filters, $perPage);
    }

    /**
     * Find a webhook by ID.
     *
     * @param int $id
     * @return Webhook|null
     */
    public function findWebhook(int $id): ?Webhook
    {
        return $this->webhookRepository->findById($id);
    }

    /**
     * Create a new webhook.
     *
     * @param array $data
     * @return Webhook
     */
    public function createWebhook(array $data): Webhook
    {
        // Generate a secret if not provided
        if (empty($data['secret'])) {
            $data['secret'] = $this->generateWebhookSecret();
        }

        return $this->webhookRepository->create($data);
    }

    /**
     * Update an existing webhook.
     *
     * @param Webhook $webhook
     * @param array $data
     * @return Webhook
     */
    public function updateWebhook(Webhook $webhook, array $data): Webhook
    {
        return $this->webhookRepository->update($webhook, $data);
    }

    /**
     * Delete a webhook.
     *
     * @param Webhook $webhook
     * @return bool
     */
    public function deleteWebhook(Webhook $webhook): bool
    {
        return $this->webhookRepository->delete($webhook);
    }

    /**
     * Get webhooks for a specific workspace.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getWorkspaceWebhooks(int $workspaceId): Collection
    {
        return $this->webhookRepository->getByWorkspace($workspaceId);
    }

    /**
     * Toggle webhook active status.
     *
     * @param Webhook $webhook
     * @return Webhook
     */
    public function toggleWebhookStatus(Webhook $webhook): Webhook
    {
        return $this->webhookRepository->toggleActive($webhook);
    }

    /**
     * Trigger webhooks for a specific event.
     *
     * @param string $event
     * @param array $payload
     * @return int Number of webhooks triggered
     */
    public function triggerWebhooks(string $event, array $payload): int
    {
        $webhooks = $this->webhookRepository->getActiveWebhooksForEvent($event);
        $triggeredCount = 0;

        foreach ($webhooks as $webhook) {
            if ($this->triggerWebhook($webhook, $event, $payload)) {
                $triggeredCount++;
            }
        }

        return $triggeredCount;
    }

    /**
     * Trigger a specific webhook.
     *
     * @param Webhook $webhook
     * @param string $event
     * @param array $payload
     * @return bool
     */
    public function triggerWebhook(Webhook $webhook, string $event, array $payload): bool
    {
        try {
            $webhookPayload = [
                'event' => $event,
                'timestamp' => now()->toISOString(),
                'data' => $payload,
                'webhook_id' => $webhook->id,
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'YourApp/1.0 Webhook',
            ];

            // Add signature if secret is set
            if ($webhook->secret) {
                $signature = $this->generateSignature($webhookPayload, $webhook->secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->retry($webhook->retry_count, 1000)
                ->post($webhook->url, $webhookPayload);

            $this->logWebhookAttempt($webhook, $event, $response->status(), $response->body());

            return $response->successful();
        } catch (\Exception $e) {
            $this->logWebhookError($webhook, $event, $e->getMessage());
            return false;
        }
    }

    /**
     * Test a webhook endpoint.
     *
     * @param Webhook $webhook
     * @return array
     */
    public function testWebhook(Webhook $webhook): array
    {
        $testPayload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toISOString(),
            'data' => [
                'message' => 'This is a test webhook payload',
                'webhook_id' => $webhook->id,
            ],
        ];

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'YourApp/1.0 Webhook Test',
            ];

            if ($webhook->secret) {
                $signature = $this->generateSignature($testPayload, $webhook->secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($webhook->url, $testPayload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response_time' => $response->transferStats?->getTransferTime(),
                'message' => $response->successful() ? 'Webhook test successful' : 'Webhook test failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 0,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a webhook secret.
     *
     * @return string
     */
    private function generateWebhookSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate webhook signature.
     *
     * @param array $payload
     * @param string $secret
     * @return string
     */
    private function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Log webhook attempt.
     *
     * @param Webhook $webhook
     * @param string $event
     * @param int $statusCode
     * @param string $response
     * @return void
     */
    private function logWebhookAttempt(Webhook $webhook, string $event, int $statusCode, string $response): void
    {
        Log::info('Webhook triggered', [
            'webhook_id' => $webhook->id,
            'webhook_name' => $webhook->name,
            'event' => $event,
            'url' => $webhook->url,
            'status_code' => $statusCode,
            'response' => substr($response, 0, 500), // Limit response length
        ]);
    }

    /**
     * Log webhook error.
     *
     * @param Webhook $webhook
     * @param string $event
     * @param string $error
     * @return void
     */
    private function logWebhookError(Webhook $webhook, string $event, string $error): void
    {
        Log::error('Webhook failed', [
            'webhook_id' => $webhook->id,
            'webhook_name' => $webhook->name,
            'event' => $event,
            'url' => $webhook->url,
            'error' => $error,
        ]);
    }
}
