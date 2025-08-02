<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExportRequest;
use App\Http\Requests\UpdateExportRequest;
use App\Http\Resources\ExportResource;
use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Export API Controller
 * 
 * Handles HTTP requests for export operations
 */
class ExportController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ExportService $exportService
    ) {
        $this->authorizeResource(Export::class, 'export');
    }

    /**
     * Display a listing of exports for the authenticated user's workspace.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaceId = $request->input('workspace_id', \Auth::user()->current_workspace_id);
        $perPage = min($request->input('per_page', 15), 100);

        $exports = $this->exportService->getWorkspaceExports($workspaceId, $perPage);

        return ExportResource::collection($exports);
    }

    /**
     * Store a newly created export.
     */
    public function store(CreateExportRequest $request): JsonResponse
    {
        $export = $this->exportService->createExport($request->validated());

        // Here you would typically dispatch a job to process the export
        // ProcessExportJob::dispatch($export);

        return response()->json([
            'message' => 'Export created successfully and will be processed shortly.',
            'data' => new ExportResource($export)
        ], 201);
    }

    /**
     * Display the specified export.
     */
    public function show(Export $export): JsonResponse
    {
        return response()->json([
            'data' => new ExportResource($export->load(['user', 'workspace']))
        ]);
    }

    /**
     * Update the specified export.
     */
    public function update(UpdateExportRequest $request, Export $export): JsonResponse
    {
        $updatedExport = $this->exportService->updateExport($export, $request->validated());

        return response()->json([
            'message' => 'Export updated successfully.',
            'data' => new ExportResource($updatedExport)
        ]);
    }

    /**
     * Remove the specified export.
     */
    public function destroy(Export $export): JsonResponse
    {
        $this->exportService->deleteExport($export);

        return response()->json([
            'message' => 'Export deleted successfully.'
        ]);
    }

    /**
     * Download the export file.
     */
    public function download(Export $export): BinaryFileResponse|JsonResponse
    {
        $this->authorize('view', $export);

        if (!$export->isReadyForDownload()) {
            return response()->json([
                'message' => 'Export is not ready for download or has expired.'
            ], 422);
        }

        $filePath = $this->exportService->downloadExport($export);
        
        if (!$filePath) {
            return response()->json([
                'message' => 'Export file not found.'
            ], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Cancel the export.
     */
    public function cancel(Export $export): JsonResponse
    {
        $this->authorize('update', $export);

        if (!$export->isInProgress()) {
            return response()->json([
                'message' => 'Export cannot be cancelled in its current state.'
            ], 422);
        }

        $updatedExport = $this->exportService->failExport($export);

        return response()->json([
            'message' => 'Export cancelled successfully.',
            'data' => new ExportResource($updatedExport)
        ]);
    }

    /**
     * Retry a failed export.
     */
    public function retry(Export $export): JsonResponse
    {
        $this->authorize('update', $export);

        if (!$export->hasFailed()) {
            return response()->json([
                'message' => 'Only failed exports can be retried.'
            ], 422);
        }

        $updatedExport = $this->exportService->updateExport($export, [
            'status' => 'pending',
            'file_path' => null
        ]);

        // Here you would typically dispatch a job to process the export
        // ProcessExportJob::dispatch($updatedExport);

        return response()->json([
            'message' => 'Export queued for retry.',
            'data' => new ExportResource($updatedExport)
        ]);
    }
}
