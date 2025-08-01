<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class SubscriptionRepository
 * 
 * Handles data access operations for subscriptions
 * 
 * @package App\Repositories
 */
class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    /**
     * @var Subscription
     */
    protected $model;

    /**
     * SubscriptionRepository constructor.
     */
    public function __construct(Subscription $model)
    {
        $this->model = $model;
    }

    /**
     * Get all subscriptions with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['workspace', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find subscription by ID
     */
    public function findById(int $id): ?Subscription
    {
        return $this->model->with(['workspace', 'plan'])->find($id);
    }

    /**
     * Create new subscription
     */
    public function create(array $data): Subscription
    {
        return $this->model->create($data);
    }

    /**
     * Update subscription
     */
    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    /**
     * Delete subscription
     */
    public function delete(Subscription $subscription): bool
    {
        return $subscription->delete();
    }

    /**
     * Find subscription by Stripe ID
     */
    public function findByStripeId(string $stripeId): ?Subscription
    {
        return $this->model->where('stripe_id', $stripeId)->first();
    }

    /**
     * Get subscriptions by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->with(['plan'])
            ->get();
    }

    /**
     * Get active subscriptions
     */
    public function getActive(): Collection
    {
        return $this->model->active()
            ->with(['workspace', 'plan'])
            ->get();
    }

    /**
     * Get subscriptions on trial
     */
    public function getOnTrial(): Collection
    {
        return $this->model->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', now())
            ->with(['workspace', 'plan'])
            ->get();
    }

    /**
     * Get subscriptions ending soon
     */
    public function getEndingSoon(int $days = 7): Collection
    {
        return $this->model->whereNotNull('ends_at')
            ->where('ends_at', '>=', now())
            ->where('ends_at', '<=', now()->addDays($days))
            ->with(['workspace', 'plan'])
            ->get();
    }
}
