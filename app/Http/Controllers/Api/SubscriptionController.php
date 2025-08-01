<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class SubscriptionController
 * 
 * Handles HTTP requests for subscription operations
 * 
 * @package App\Http\Controllers\Api
 */
class SubscriptionController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * SubscriptionController constructor.
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->authorizeResource(Subscription::class, 'subscription');
    }

    /**
     * Display a listing of subscriptions.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $subscriptions = $this->subscriptionService->getAllPaginated($perPage);

        return SubscriptionResource::collection($subscriptions);
    }

    /**
     * Store a newly created subscription.
     * 
     * @param CreateSubscriptionRequest $request
     * @return SubscriptionResource
     */
    public function store(CreateSubscriptionRequest $request): SubscriptionResource
    {
        $subscription = $this->subscriptionService->create($request->validated());

        return new SubscriptionResource($subscription);
    }

    /**
     * Display the specified subscription.
     * 
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function show(Subscription $subscription): SubscriptionResource
    {
        return new SubscriptionResource($subscription->load(['workspace', 'plan']));
    }

    /**
     * Update the specified subscription.
     * 
     * @param UpdateSubscriptionRequest $request
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): SubscriptionResource
    {
        $this->subscriptionService->update($subscription, $request->validated());

        return new SubscriptionResource($subscription->fresh(['workspace', 'plan']));
    }

    /**
     * Remove the specified subscription.
     * 
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->subscriptionService->delete($subscription);

        return response()->json([
            'message' => 'Subscription deleted successfully'
        ], 204);
    }

    /**
     * Activate subscription.
     * 
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function activate(Subscription $subscription): SubscriptionResource
    {
        $this->authorize('update', $subscription);
        
        $this->subscriptionService->activate($subscription);

        return new SubscriptionResource($subscription->fresh(['workspace', 'plan']));
    }

    /**
     * Deactivate subscription.
     * 
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function deactivate(Subscription $subscription): SubscriptionResource
    {
        $this->authorize('update', $subscription);
        
        $this->subscriptionService->deactivate($subscription);

        return new SubscriptionResource($subscription->fresh(['workspace', 'plan']));
    }

    /**
     * Cancel subscription.
     * 
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function cancel(Subscription $subscription): SubscriptionResource
    {
        $this->authorize('update', $subscription);
        
        $this->subscriptionService->cancel($subscription);

        return new SubscriptionResource($subscription->fresh(['workspace', 'plan']));
    }

    /**
     * Resume subscription.
     * 
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function resume(Subscription $subscription): SubscriptionResource
    {
        $this->authorize('update', $subscription);
        
        $this->subscriptionService->resume($subscription);

        return new SubscriptionResource($subscription->fresh(['workspace', 'plan']));
    }

    /**
     * Update subscription quantity.
     * 
     * @param Request $request
     * @param Subscription $subscription
     * @return SubscriptionResource
     */
    public function updateQuantity(Request $request, Subscription $subscription): SubscriptionResource
    {
        $this->authorize('update', $subscription);
        
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1']
        ]);

        $this->subscriptionService->updateQuantity($subscription, $request->quantity);

        return new SubscriptionResource($subscription->fresh(['workspace', 'plan']));
    }
}
