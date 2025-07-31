<?php

namespace App\Services;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Plan Service
 * 
 * Handles business logic for plan operations
 */
class PlanService
{
    /**
     * Plan repository instance
     */
    protected PlanRepositoryInterface $planRepository;

    public function __construct(PlanRepositoryInterface $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    /**
     * Get all plans with filters
     */
    public function getAllPlans(array $filters = []): Collection
    {
        return $this->planRepository->all($filters);
    }

    /**
     * Get paginated plans
     */
    public function getPaginatedPlans(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->planRepository->paginate($perPage, $filters);
    }

    /**
     * Find plan by ID
     */
    public function findPlan(int $id): ?Plan
    {
        return $this->planRepository->find($id);
    }

    /**
     * Find plan by slug
     */
    public function findPlanBySlug(string $slug): ?Plan
    {
        return $this->planRepository->findBySlug($slug);
    }

    /**
     * Create new plan
     */
    public function createPlan(array $data): Plan
    {
        // Auto-generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure slug uniqueness
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        return $this->planRepository->create($data);
    }

    /**
     * Update existing plan
     */
    public function updatePlan(Plan $plan, array $data): Plan
    {
        // Handle slug updates
        if (isset($data['slug']) && $data['slug'] !== $plan->slug) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $plan->id);
        }

        return $this->planRepository->update($plan, $data);
    }

    /**
     * Delete plan
     */
    public function deletePlan(Plan $plan): bool
    {
        // Check if plan has active subscriptions
        if ($plan->subscriptions()->exists()) {
            throw new \Exception('Cannot delete plan with active subscriptions');
        }

        return $this->planRepository->delete($plan);
    }

    /**
     * Activate plan
     */
    public function activatePlan(Plan $plan): Plan
    {
        return $this->planRepository->update($plan, ['is_active' => true]);
    }

    /**
     * Deactivate plan
     */
    public function deactivatePlan(Plan $plan): Plan
    {
        return $this->planRepository->update($plan, ['is_active' => false]);
    }

    /**
     * Mark plan as popular
     */
    public function markAsPopular(Plan $plan): Plan
    {
        return $this->planRepository->update($plan, ['is_popular' => true]);
    }

    /**
     * Remove popular status
     */
    public function removePopularStatus(Plan $plan): Plan
    {
        return $this->planRepository->update($plan, ['is_popular' => false]);
    }

    /**
     * Get active plans
     */
    public function getActivePlans(): Collection
    {
        return $this->planRepository->getActive();
    }

    /**
     * Get popular plans
     */
    public function getPopularPlans(): Collection
    {
        return $this->planRepository->getPopular();
    }

    /**
     * Calculate annual savings
     */
    public function calculateAnnualSavings(Plan $monthlyPlan, Plan $yearlyPlan): float
    {
        $monthlyYearlyTotal = $monthlyPlan->price * 12;
        return $monthlyYearlyTotal - $yearlyPlan->price;
    }

    /**
     * Ensure slug uniqueness
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Plan::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
