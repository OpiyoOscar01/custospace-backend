<?php

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Plan Repository Interface
 * 
 * Defines contract for plan data access operations
 */
interface PlanRepositoryInterface
{
    /**
     * Get all plans with optional filters
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated plans
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find plan by ID
     */
    public function find(int $id): ?Plan;

    /**
     * Find plan by slug
     */
    public function findBySlug(string $slug): ?Plan;

    /**
     * Create new plan
     */
    public function create(array $data): Plan;

    /**
     * Update existing plan
     */
    public function update(Plan $plan, array $data): Plan;

    /**
     * Delete plan
     */
    public function delete(Plan $plan): bool;

    /**
     * Get active plans
     */
    public function getActive(): Collection;

    /**
     * Get popular plans
     */
    public function getPopular(): Collection;

    /**
     * Get plans by billing cycle
     */
    public function getByBillingCycle(string $cycle): Collection;
}
