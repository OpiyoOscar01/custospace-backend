<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateImportRequest;
use App\Http\Requests\UpdateImportRequest;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Import API Controller
 * 
 * Handles HTTP requests for import operations
 */
class ImportController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ImportService $importService
    ) {
        $this->authorizeResource(Import::class, 'import');
    }

    /**
     * Display a listing of imports for the authenticated user's workspace.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaceId = $request->input('workspace_id', \Auth::user()->current_workspace_id);
        $perPage = min($request->input('per_page', 15), 100);

        $imports = $this->importService->getWorkspaceImports($workspaceId, $perPage);

        return ImportResource::collection($imports);
    }

    /**
     * Store a newly created import.
     */
    public function store(CreateImportRequest $request): JsonResponse
    {
        $import = $this->importService->createImport(
            $request->validated(),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Import created successfully.',
            'data' => new ImportResource($import)
        ], 201);
    }

    /**
     * Display the specified import.
     */
    public function show(Import $import): JsonResponse
    {
        return response()->json([
            'data' => new ImportResource($import->load(['user', 'workspace']))
        ]);
    }

    /**
     * Update the specified import.
     */
    public function update(UpdateImportRequest $request, Import $import): JsonResponse
    {
        $updatedImport = $this->importService->updateImport($import, $request->validated());

        return response()->json([
            'message' => 'Import updated successfully.',
            'data' => new ImportResource($updatedImport)
        ]);
    }

    /**
     * Remove the specified import.
     */
    public function destroy(Import $import): JsonResponse
    {
        $this->importService->deleteImport($import);

        return response()->json([
            'message' => 'Import deleted successfully.'
        ]);
    }

    /**
     * Start processing the import.
     */
    public function process(Import $import): JsonResponse
    {
        $this->authorize('update', $import);

        if (!$import->isInProgress()) {
            return response()->json([
                'message' => 'Import cannot be processed in its current state.'
            ], 422);
        }

        $updatedImport = $this->importService->startProcessing($import);

        // Here you would typically dispatch a job to process the import
        // ProcessImportJob::dispatch($updatedImport);

        return response()->json([
            'message' => 'Import processing started.',
            'data' => new ImportResource($updatedImport)
        ]);
    }

    /**
     * Cancel the import.
     */
    public function cancel(Import $import): JsonResponse
    {
        $this->authorize('update', $import);

        if (!$import->isInProgress()) {
            return response()->json([
                'message' => 'Import cannot be cancelled in its current state.'
            ], 422);
        }

        $updatedImport = $this->importService->failImport($import, ['reason' => 'Cancelled by user']);

        return response()->json([
            'message' => 'Import cancelled successfully.',
            'data' => new ImportResource($updatedImport)
        ]);
    }

    /**
     * Retry a failed import.
     */
    public function retry(Import $import): JsonResponse
    {
        $this->authorize('update', $import);

        if (!$import->hasFailed()) {
            return response()->json([
                'message' => 'Only failed imports can be retried.'
            ], 422);
        }

        $updatedImport = $this->importService->updateImport($import, [
            'status' => 'pending',
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'errors' => []
        ]);

        return response()->json([
            'message' => 'Import queued for retry.',
            'data' => new ImportResource($updatedImport)
        ]);
    }
}
