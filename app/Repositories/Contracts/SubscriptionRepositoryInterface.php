<?php

namespace App\Repositories\Contracts;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface SubscriptionRepositoryInterface
 * 
 * Defines contract for subscription data access operations
 * 
 * @package App\Repositories\Contracts
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Get all subscriptions with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find subscription by ID
     */
    public function findById(int $id): ?Subscription;

    /**
     * Create new subscription
     */
    public function create(array $data): Subscription;

    /**
     * Update subscription
     */
    public function update(Subscription $subscription, array $data): bool;

    /**
     * Delete subscription
     */
    public function delete(Subscription $subscription): bool;

    /**
     * Find subscription by Stripe ID
     */
    public function findByStripeId(string $stripeId): ?Subscription;

    /**
     * Get subscriptions by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get active subscriptions
     */
    public function getActive(): Collection;

    /**
     * Get subscriptions on trial
     */
    public function getOnTrial(): Collection;

    /**
     * Get subscriptions ending soon
     */
    public function getEndingSoon(int $days = 7): Collection;
}
