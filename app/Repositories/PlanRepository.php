<?php

namespace App\Repositories;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Plan Repository Implementation
 * 
 * Handles all database operations for plans
 */
class PlanRepository implements PlanRepositoryInterface
{
    /**
     * Plan model instance
     */
    protected Plan $model;

    public function __construct(Plan $model)
    {
        $this->model = $model;
    }

    /**
     * Get all plans with optional filters
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_popular'])) {
            $query->where('is_popular', $filters['is_popular']);
        }

        if (isset($filters['billing_cycle'])) {
            $query->where('billing_cycle', $filters['billing_cycle']);
        }

        return $query->orderBy('price')->get();
    }

    /**
     * Get paginated plans
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_popular'])) {
            $query->where('is_popular', $filters['is_popular']);
        }

        if (isset($filters['billing_cycle'])) {
            $query->where('billing_cycle', $filters['billing_cycle']);
        }

        return $query->orderBy('price')->paginate($perPage);
    }

    /**
     * Find plan by ID
     */
    public function find(int $id): ?Plan
    {
        return $this->model->find($id);
    }

    /**
     * Find plan by slug
     */
    public function findBySlug(string $slug): ?Plan
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Create new plan
     */
    public function create(array $data): Plan
    {
        return $this->model->create($data);
    }

    /**
     * Update existing plan
     */
    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);
        return $plan->fresh();
    }

    /**
     * Delete plan
     */
    public function delete(Plan $plan): bool
    {
        return $plan->delete();
    }

    /**
     * Get active plans
     */
    public function getActive(): Collection
    {
        return $this->model->active()->orderBy('price')->get();
    }

    /**
     * Get popular plans
     */
    public function getPopular(): Collection
    {
        return $this->model->popular()->active()->orderBy('price')->get();
    }

    /**
     * Get plans by billing cycle
     */
    public function getByBillingCycle(string $cycle): Collection
    {
        return $this->model->billingCycle($cycle)->active()->orderBy('price')->get();
    }
}
