<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWebhookDeliveryRequest;
use App\Http\Requests\UpdateWebhookDeliveryRequest;
use App\Http\Resources\WebhookDeliveryResource;
use App\Models\WebhookDelivery;
use App\Services\WebhookDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * WebhookDelivery API Controller
 * 
 * Handles webhook delivery CRUD operations and custom actions
 */
class WebhookDeliveryController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private WebhookDeliveryService $webhookDeliveryService
    ) {
        $this->authorizeResource(WebhookDelivery::class, 'webhook_delivery');
    }

    /**
     * Display a listing of webhook deliveries
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'webhook_id',
            'status',
            'event',
            'date_from',
            'date_to'
        ]);

        $perPage = $request->integer('per_page', 15);
        $webhookDeliveries = $this->webhookDeliveryService->getWebhookDeliveries($filters, $perPage);

        return WebhookDeliveryResource::collection($webhookDeliveries);
    }

    /**
     * Store a newly created webhook delivery
     * 
     * @param CreateWebhookDeliveryRequest $request
     * @return WebhookDeliveryResource
     */
    public function store(CreateWebhookDeliveryRequest $request): WebhookDeliveryResource
    {
        $webhookDelivery = $this->webhookDeliveryService->createWebhookDelivery(
            $request->validated()
        );

        return new WebhookDeliveryResource($webhookDelivery);
    }

    /**
     * Display the specified webhook delivery
     * 
     * @param WebhookDelivery $webhookDelivery
     * @return WebhookDeliveryResource
     */
    public function show(WebhookDelivery $webhookDelivery): WebhookDeliveryResource
    {
        return new WebhookDeliveryResource($webhookDelivery->load(['webhook']));
    }

    /**
     * Update the specified webhook delivery
     * 
     * @param UpdateWebhookDeliveryRequest $request
     * @param WebhookDelivery $webhookDelivery
     * @return WebhookDeliveryResource
     */
    public function update(UpdateWebhookDeliveryRequest $request, WebhookDelivery $webhookDelivery): WebhookDeliveryResource
    {
        $updatedWebhookDelivery = $this->webhookDeliveryService->updateWebhookDelivery(
            $webhookDelivery,
            $request->validated()
        );

        return new WebhookDeliveryResource($updatedWebhookDelivery);
    }

    /**
     * Remove the specified webhook delivery
     * 
     * @param WebhookDelivery $webhookDelivery
     * @return JsonResponse
     */
    public function destroy(WebhookDelivery $webhookDelivery): JsonResponse
    {
        $this->webhookDeliveryService->deleteWebhookDelivery($webhookDelivery);

        return response()->json([
            'message' => 'Webhook delivery deleted successfully'
        ]);
    }

    /**
     * Retry failed webhook delivery
     * 
     * @param WebhookDelivery $webhookDelivery
     * @return WebhookDeliveryResource
     */
    public function retry(WebhookDelivery $webhookDelivery): WebhookDeliveryResource
    {
        $this->authorize('retry', $webhookDelivery);

        try {
            $retried = $this->webhookDeliveryService->retryDelivery($webhookDelivery);
            return new WebhookDeliveryResource($retried);
        } catch (\InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }
    }

    /**
     * Mark webhook delivery as delivered
     * 
     * @param Request $request
     * @param WebhookDelivery $webhookDelivery
     * @return WebhookDeliveryResource
     */
    public function markDelivered(Request $request, WebhookDelivery $webhookDelivery): WebhookDeliveryResource
    {
        $this->authorize('update', $webhookDelivery);

        $request->validate([
            'response_code' => 'required|integer|min:200|max:299',
            'response_body' => 'nullable|string'
        ]);

        $updated = $this->webhookDeliveryService->updateWebhookDelivery($webhookDelivery, [
            'status' => WebhookDelivery::STATUS_DELIVERED,
            'response_code' => $request->response_code,
            'response_body' => $request->response_body,
            'next_attempt_at' => null,
        ]);

        return new WebhookDeliveryResource($updated);
    }

    /**
     * Mark webhook delivery as failed
     * 
     * @param Request $request
     * @param WebhookDelivery $webhookDelivery
     * @return WebhookDeliveryResource
     */
    public function markFailed(Request $request, WebhookDelivery $webhookDelivery): WebhookDeliveryResource
    {
        $this->authorize('update', $webhookDelivery);

        $request->validate([
            'response_code' => 'required|integer|min:400',
            'response_body' => 'required|string'
        ]);

        $updated = $this->webhookDeliveryService->updateWebhookDelivery($webhookDelivery, [
            'status' => WebhookDelivery::STATUS_FAILED,
            'response_code' => $request->response_code,
            'response_body' => $request->response_body,
            'next_attempt_at' => now()->addMinutes(pow(2, $webhookDelivery->attempts)),
        ]);

        return new WebhookDeliveryResource($updated);
    }

    /**
     * Get webhook delivery statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WebhookDelivery::class);

        $webhookId = $request->integer('webhook_id');
        $stats = $this->webhookDeliveryService->getDeliveryStats($webhookId);

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Process failed deliveries for retry
     * 
     * @return JsonResponse
     */
    public function processFailedDeliveries(): JsonResponse
    {
        $this->authorize('processFailedDeliveries', WebhookDelivery::class);

        $processedCount = $this->webhookDeliveryService->processFailedDeliveries();

        return response()->json([
            'message' => "Processed {$processedCount} failed deliveries",
            'processed_count' => $processedCount
        ]);
    }
}
