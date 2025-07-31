<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Plan API Controller
 * 
 * Handles HTTP requests for subscription plan management
 */
class PlanController extends Controller
{
    use AuthorizesRequests;
    /**
     * Plan service instance
     */
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {

        $this->planService = $planService;
        
        // Apply authorization middleware
        $this->authorizeResource(Plan::class, 'plan');
    }

    /**
     * Display a listing of plans
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['is_active', 'is_popular', 'billing_cycle']);
        $perPage = $request->get('per_page', 15);

        $plans = $this->planService->getPaginatedPlans($perPage, $filters);

        return PlanResource::collection($plans);
    }

    /**
     * Store a newly created plan
     */
    public function store(CreatePlanRequest $request): PlanResource
    {
        $plan = $this->planService->createPlan($request->validated());

        return new PlanResource($plan);
    }

    /**
     * Display the specified plan
     */
    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan);
    }

    /**
     * Update the specified plan
     */
    public function update(UpdatePlanRequest $request, Plan $plan): PlanResource
    {
        $updatedPlan = $this->planService->updatePlan($plan, $request->validated());

        return new PlanResource($updatedPlan);
    }

    /**
     * Remove the specified plan
     */
    public function destroy(Plan $plan): JsonResponse
    {
        try {
            $this->planService->deletePlan($plan);
            return response()->json(['message' => 'Plan deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Activate plan
     */
    public function activate(Plan $plan): PlanResource
    {
        $this->authorize('update', $plan);
        
        $activatedPlan = $this->planService->activatePlan($plan);

        return new PlanResource($activatedPlan);
    }

    /**
     * Deactivate plan
     */
    public function deactivate(Plan $plan): PlanResource
    {
        $this->authorize('update', $plan);
        
        $deactivatedPlan = $this->planService->deactivatePlan($plan);

        return new PlanResource($deactivatedPlan);
    }

    /**
     * Mark plan as popular
     */
    public function markPopular(Plan $plan): PlanResource
    {
        $this->authorize('update', $plan);
        
        $popularPlan = $this->planService->markAsPopular($plan);

        return new PlanResource($popularPlan);
    }

    /**
     * Remove popular status from plan
     */
    public function removePopular(Plan $plan): PlanResource
    {
        $this->authorize('update', $plan);
        
        $plan = $this->planService->removePopularStatus($plan);

        return new PlanResource($plan);
    }

    /**
     * Get active plans only
     */
    public function active(): AnonymousResourceCollection
    {
        $plans = $this->planService->getActivePlans();

        return PlanResource::collection($plans);
    }

    /**
     * Get popular plans only
     */
    public function popular(): AnonymousResourceCollection
    {
        $plans = $this->planService->getPopularPlans();

        return PlanResource::collection($plans);
    }

    /**
     * Find plan by slug
     */
    public function findBySlug(string $slug): PlanResource
    {
        $plan = $this->planService->findPlanBySlug($slug);

        if (!$plan) {
            abort(404, 'Plan not found');
        }

        $this->authorize('view', $plan);

        return new PlanResource($plan);
    }
}
