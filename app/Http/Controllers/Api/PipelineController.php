<?php
// app/Http/Controllers/Api/PipelineController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePipelineRequest;
use App\Http\Requests\UpdatePipelineRequest;
use App\Http\Resources\PipelineResource;
use App\Models\Pipeline;
use App\Services\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Pipeline API Controller
 * 
 * Handles HTTP requests for pipeline management operations.
 */
class PipelineController extends Controller
{
    public function __construct(
        private PipelineService $pipelineService
    ) {
    }

    /**
     * Display a listing of pipelines.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Pipeline::class);

        // Extract filters from request
        $filters = $request->only([
            'workspace_id', 'project_id', 'is_default', 'search', 'sort_by', 'sort_direction'
        ]);

        $perPage = $request->get('per_page', 15);
        $pipelines = $this->pipelineService->getAllPipelinesPaginated($filters, $perPage);

        return PipelineResource::collection($pipelines);
    }

    /**
     * Store a newly created pipeline.
     * 
     * @param CreatePipelineRequest $request
     * @return JsonResponse
     */
    public function store(CreatePipelineRequest $request): JsonResponse
    {
        Gate::authorize('create', Pipeline::class);

        $validatedData = $request->validated();
        $statusIds = $validatedData['statuses'] ?? [];
        unset($validatedData['statuses']);

        $pipeline = $this->pipelineService->createPipeline($validatedData, $statusIds);

        return response()->json([
            'message' => 'Pipeline created successfully.',
            'data' => new PipelineResource($pipeline)
        ], 201);
    }

    /**
     * Display the specified pipeline.
     * 
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function show(Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('view', $pipeline);

        return response()->json([
            'data' => new PipelineResource($pipeline->load(['statuses']))
        ]);
    }

    /**
     * Update the specified pipeline.
     * 
     * @param UpdatePipelineRequest $request
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function update(UpdatePipelineRequest $request, Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('update', $pipeline);

        $updatedPipeline = $this->pipelineService->updatePipeline($pipeline, $request->validated());

        return response()->json([
            'message' => 'Pipeline updated successfully.',
            'data' => new PipelineResource($updatedPipeline)
        ]);
    }

    /**
     * Remove the specified pipeline.
     * 
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function destroy(Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('delete', $pipeline);

        $this->pipelineService->deletePipeline($pipeline);

        return response()->json([
            'message' => 'Pipeline deleted successfully.'
        ]);
    }

    /**
     * List pipelines by workspace.
     * 
     * @param int $workspaceId
     * @return JsonResponse
     */
    public function byWorkspace(int $workspaceId): JsonResponse
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Pipeline::class);

        $pipelines = $this->pipelineService->getPipelinesByWorkspace($workspaceId);

        return response()->json([
            'data' => PipelineResource::collection($pipelines)
        ]);
    }

    /**
     * List pipelines by project.
     * 
     * @param int $projectId
     * @return JsonResponse
     */
    public function byProject(int $projectId): JsonResponse
    {
        // Authorization handled by policy
        Gate::authorize('viewAny', Pipeline::class);

        $pipelines = $this->pipelineService->getPipelinesByProject($projectId);

        return response()->json([
            'data' => PipelineResource::collection($pipelines)
        ]);
    }

    /**
     * Set pipeline as default.
     * 
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function setAsDefault(Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('update', $pipeline);

        $result = $this->pipelineService->setAsDefault($pipeline);

        if ($result) {
            return response()->json([
                'message' => 'Pipeline set as default successfully.',
                'data' => new PipelineResource($pipeline->fresh())
            ]);
        }

        return response()->json([
            'message' => 'Failed to set pipeline as default.'
        ], 500);
    }

    /**
     * Sync statuses to pipeline.
     * 
     * @param Request $request
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function syncStatuses(Request $request, Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('update', $pipeline);

        $request->validate([
            'statuses' => 'required|array',
            'statuses.*' => 'integer|exists:statuses,id'
        ]);

        $updatedPipeline = $this->pipelineService->syncStatuses($pipeline, $request->statuses);

        return response()->json([
            'message' => 'Pipeline statuses updated successfully.',
            'data' => new PipelineResource($updatedPipeline)
        ]);
    }

    /**
     * Add status to pipeline.
     * 
     * @param Request $request
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function addStatus(Request $request, Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('update', $pipeline);

        $request->validate([
            'status_id' => 'required|integer|exists:statuses,id',
            'order' => 'nullable|integer|min:0'
        ]);

        $updatedPipeline = $this->pipelineService->addStatus(
            $pipeline, 
            $request->status_id, 
            $request->order
        );

        return response()->json([
            'message' => 'Status added to pipeline successfully.',
            'data' => new PipelineResource($updatedPipeline)
        ]);
    }

    /**
     * Remove status from pipeline.
     * 
     * @param Request $request
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function removeStatus(Request $request, Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('update', $pipeline);

        $request->validate([
            'status_id' => 'required|integer|exists:statuses,id'
        ]);

        $updatedPipeline = $this->pipelineService->removeStatus($pipeline, $request->status_id);

        return response()->json([
            'message' => 'Status removed from pipeline successfully.',
            'data' => new PipelineResource($updatedPipeline)
        ]);
    }

    /**
     * Reorder statuses in pipeline.
     * 
     * @param Request $request
     * @param Pipeline $pipeline
     * @return JsonResponse
     */
    public function reorderStatuses(Request $request, Pipeline $pipeline): JsonResponse
    {
        Gate::authorize('update', $pipeline);

        $request->validate([
            'statuses_order' => 'required|array',
            'statuses_order.*' => 'integer|min:0'
        ]);

        $updatedPipeline = $this->pipelineService->reorderStatuses($pipeline, $request->statuses_order);

        return response()->json([
            'message' => 'Pipeline statuses reordered successfully.',
            'data' => new PipelineResource($updatedPipeline)
        ]);
    }

    /**
     * Create default pipeline for workspace.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createDefaultPipelineForWorkspace(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id'
        ]);

        Gate::authorize('create', Pipeline::class);

        $pipeline = $this->pipelineService->createDefaultPipelineForWorkspace($request->workspace_id);

        return response()->json([
            'message' => 'Default pipeline created successfully.',
            'data' => new PipelineResource($pipeline)
        ], 201);
    }
}
