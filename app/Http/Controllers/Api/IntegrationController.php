<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateIntegrationRequest;
use App\Http\Requests\UpdateIntegrationRequest;
use App\Http\Resources\IntegrationResource;
use App\Models\Integration;
use App\Services\IntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Integration API Controller
 * 
 * Handles HTTP requests for integration management
 */
class IntegrationController extends Controller
{
    use AuthorizesRequests;
    /**
     * Integration service instance
     */
    protected IntegrationService $integrationService;

    public function __construct(IntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
        
        // Apply authorization middleware
        $this->authorizeResource(Integration::class, 'integration');
    }

    /**
     * Display a listing of integrations
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['workspace_id', 'type', 'is_active']);
        $perPage = $request->get('per_page', 15);

        $integrations = $this->integrationService->getPaginatedIntegrations($perPage, $filters);

        return IntegrationResource::collection($integrations);
    }

    /**
     * Store a newly created integration
     */
    public function store(CreateIntegrationRequest $request): IntegrationResource
    {
        $integration = $this->integrationService->createIntegration($request->validated());

        return new IntegrationResource($integration);
    }

    /**
     * Display the specified integration
     */
    public function show(Integration $integration): IntegrationResource
    {
        return new IntegrationResource($integration->load('workspace'));
    }

    /**
     * Update the specified integration
     */
    public function update(UpdateIntegrationRequest $request, Integration $integration): IntegrationResource
    {
        $updatedIntegration = $this->integrationService->updateIntegration(
            $integration,
            $request->validated()
        );

        return new IntegrationResource($updatedIntegration);
    }

    /**
     * Remove the specified integration
     */
    public function destroy(Integration $integration): JsonResponse
    {
        $this->integrationService->deleteIntegration($integration);

        return response()->json(['message' => 'Integration deleted successfully']);
    }

    /**
     * Activate integration
     */
    public function activate(Integration $integration): IntegrationResource
    {
        $this->authorize('update', $integration);
        
        $activatedIntegration = $this->integrationService->activateIntegration($integration);

        return new IntegrationResource($activatedIntegration);
    }

    /**
     * Deactivate integration
     */
    public function deactivate(Integration $integration): IntegrationResource
    {
        $this->authorize('update', $integration);
        
        $deactivatedIntegration = $this->integrationService->deactivateIntegration($integration);

        return new IntegrationResource($deactivatedIntegration);
    }

    /**
     * Test integration connection
     */
    public function testConnection(Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);
        
        $result = $this->integrationService->testConnection($integration);

        return response()->json($result);
    }

    /**
     * Get integrations by workspace
     */
    public function getByWorkspace(Request $request, int $workspaceId): AnonymousResourceCollection
    {
        $integrations = $this->integrationService->getWorkspaceIntegrations($workspaceId);

        return IntegrationResource::collection($integrations);
    }
}

