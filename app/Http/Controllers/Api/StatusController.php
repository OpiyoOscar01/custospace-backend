<?php
// app/Http/Controllers/Api/StatusController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStatusRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Status API Controller
 * 
 * Handles HTTP requests for status management operations.
 */
class StatusController extends Controller
{
    public function __construct(
        private StatusService $statusService
    ) {
    }

    /**
     * Display a listing of statuses.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Status::class);

        // Extract filters from request
        $filters = $request->only([
            'workspace_id', 'type', 'is_default', 'search', 'sort_by', 'sort_direction'
        ]);

        $perPage = $request->get('per_page', 15);
        $statuses = $this->statusService->getAllStatusesPaginated($filters, $perPage);

        return StatusResource::collection($statuses);
    }

    /**
     * Store a newly created status.
     * 
     * @param CreateStatusRequest $request
     * @return JsonResponse
     */
    public function store(CreateStatusRequest $request): JsonResponse
    {
        Gate::authorize('create', Status::class);

        $status = $this->statusService->createStatus($request->validated());

        return response()->json([
            'message' => 'Status created successfully.',
            'data' => new StatusResource($status)
        ], 201);
    }

    /**
     * Display the specified status.
     * 
     * @param Status $status
     * @return JsonResponse
     */
    public function show(Status $status): JsonResponse
    {
        Gate::authorize('view', $status);

        return response()->json([
            'data' => new StatusResource($status)
        ]);
    }

    /**
     * Update the specified status.
     * 
     * @param UpdateStatusRequest $request
     * @param Status $status
     * @return JsonResponse
     */
    public function update(UpdateStatusRequest $request, Status $status): JsonResponse
    {
        Gate::authorize('update', $status);

        $updatedStatus = $this->statusService->updateStatus($status, $request->validated());

        return response()->json([
            'message' => 'Status updated successfully.',
            'data' => new StatusResource($updatedStatus)
        ]);
    }

    /**
     * Remove the specified status.
     * 
     * @param Status $status
     * @return JsonResponse
     */
    public function destroy(Status $status): JsonResponse
    {
        Gate::authorize('delete', $status);

        $this->statusService->deleteStatus($status);

        return response()->json([
            'message' => 'Status deleted successfully.'
        ]);
    }

    /**
     * List statuses by workspace.
     * 
     * @param Request $request
     * @param int $workspaceId
     * @return JsonResponse
     */
    public function byWorkspace(Request $request, int $workspaceId): JsonResponse
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Status::class);

        $filters = $request->only(['type', 'is_default']);
        $statuses = $this->statusService->getStatusesByWorkspace($workspaceId, $filters);

        return response()->json([
            'data' => StatusResource::collection($statuses)
        ]);
    }

    /**
     * List statuses by type.
     * 
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Status::class);

        $workspaceId = $request->get('workspace_id');
        $statuses = $this->statusService->getStatusesByType($type, $workspaceId);

        return response()->json([
            'data' => StatusResource::collection($statuses)
        ]);
    }

    /**
     * Create default statuses for a workspace.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createDefaultStatuses(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id'
        ]);

        Gate::authorize('create', Status::class);

        $statuses = $this->statusService->createDefaultStatuses($request->workspace_id);

        return response()->json([
            'message' => 'Default statuses created successfully.',
            'data' => StatusResource::collection($statuses)
        ], 201);
    }
}
