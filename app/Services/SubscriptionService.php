<?php

namespace App\Services;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class SubscriptionService
 * 
 * Handles business logic for subscription operations
 * 
 * @package App\Services
 */
class SubscriptionService
{
    /**
     * @var SubscriptionRepositoryInterface
     */
    protected $subscriptionRepository;

    /**
     * SubscriptionService constructor.
     */
    public function __construct(SubscriptionRepositoryInterface $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Get all subscriptions with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getAllPaginated($perPage);
    }

    /**
     * Find subscription by ID
     */
    public function findById(int $id): ?Subscription
    {
        return $this->subscriptionRepository->findById($id);
    }

    /**
     * Create new subscription
     */
    public function create(array $data): Subscription
    {
        // Business logic for subscription creation
        $data['quantity'] = $data['quantity'] ?? 1;
        
        return $this->subscriptionRepository->create($data);
    }

    /**
     * Update subscription
     */
    public function update(Subscription $subscription, array $data): bool
    {
        // Business logic for subscription update
        return $this->subscriptionRepository->update($subscription, $data);
    }

    /**
     * Delete subscription
     */
    public function delete(Subscription $subscription): bool
    {
        // Business logic before deletion (e.g., cancel Stripe subscription)
        return $this->subscriptionRepository->delete($subscription);
    }

    /**
     * Activate subscription
     */
    public function activate(Subscription $subscription): bool
    {
        return $this->subscriptionRepository->update($subscription, [
            'stripe_status' => 'active',
            'ends_at' => null, // Remove end date when activating
        ]);
    }

    /**
     * Deactivate subscription
     */
    public function deactivate(Subscription $subscription): bool
    {
        return $this->subscriptionRepository->update($subscription, [
            'stripe_status' => 'inactive',
            'ends_at' => now(),
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription): bool
    {
        return $this->subscriptionRepository->update($subscription, [
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);
    }

    /**
     * Resume subscription
     */
    public function resume(Subscription $subscription): bool
    {
        return $this->subscriptionRepository->update($subscription, [
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);
    }

    /**
     * Update subscription quantity
     */
    public function updateQuantity(Subscription $subscription, int $quantity): bool
    {
        // Business logic for quantity update (e.g., update Stripe subscription)
        return $this->subscriptionRepository->update($subscription, [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Get subscriptions by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return $this->subscriptionRepository->getByWorkspace($workspaceId);
    }

    /**
     * Get active subscriptions
     */
    public function getActive(): Collection
    {
        return $this->subscriptionRepository->getActive();
    }

    /**
     * Get subscriptions ending soon
     */
    public function getEndingSoon(int $days = 7): Collection
    {
        return $this->subscriptionRepository->getEndingSoon($days);
    }

    /**
     * Check if workspace has active subscription
     */
    public function hasActiveSubscription(int $workspaceId): bool
    {
        return $this->subscriptionRepository->getByWorkspace($workspaceId)
            ->filter(function ($subscription) {
                return $subscription->isActive() && !$subscription->hasEnded();
            })
            ->isNotEmpty();
    }
}
