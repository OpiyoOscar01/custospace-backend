<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WebhookController extends Controller
{
    use AuthorizesRequests;
    /**
     * Create a new WebhookController instance.
     */
    public function __construct(
        private WebhookService $webhookService
    ) {
        $this->authorizeResource(Webhook::class, 'webhook');
    }

    /**
     * Display a listing of webhooks.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['workspace_id', 'is_active', 'event']);
        $perPage = $request->get('per_page', 15);

        $webhooks = $this->webhookService->getAllWebhooks($filters, $perPage);

        return WebhookResource::collection($webhooks);
    }

    /**
     * Store a newly created webhook.
     *
     * @param CreateWebhookRequest $request
     * @return WebhookResource
     */
    public function store(CreateWebhookRequest $request): WebhookResource
    {
        $webhook = $this->webhookService->createWebhook($request->validated());

        return new WebhookResource($webhook);
    }

    /**
     * Display the specified webhook.
     *
     * @param Webhook $webhook
     * @return WebhookResource
     */
    public function show(Webhook $webhook): WebhookResource
    {
        return new WebhookResource($webhook->load(['workspace']));
    }

    /**
     * Update the specified webhook.
     *
     * @param UpdateWebhookRequest $request
     * @param Webhook $webhook
     * @return WebhookResource
     */
    public function update(UpdateWebhookRequest $request, Webhook $webhook): WebhookResource
    {
        $updatedWebhook = $this->webhookService->updateWebhook($webhook, $request->validated());

        return new WebhookResource($updatedWebhook);
    }

    /**
     * Remove the specified webhook.
     *
     * @param Webhook $webhook
     * @return JsonResponse
     */
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->webhookService->deleteWebhook($webhook);

        return response()->json([
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Toggle webhook active status.
     *
     * @param Webhook $webhook
     * @return WebhookResource
     */
    public function toggleStatus(Webhook $webhook): WebhookResource
    {
        $this->authorize('update', $webhook);

        $updatedWebhook = $this->webhookService->toggleWebhookStatus($webhook);

        return new WebhookResource($updatedWebhook);
    }

    /**
     * Test the specified webhook.
     *
     * @param Webhook $webhook
     * @return JsonResponse
     */
    public function test(Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $result = $this->webhookService->testWebhook($webhook);

        return response()->json([
            'message' => 'Webhook test completed',
            'result' => $result,
        ]);
    }
}
