<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBackupRequest;
use App\Http\Requests\UpdateBackupRequest;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
/**
 * Backup API Controller
 * 
 * Handles all API endpoints for backup operations
 */
class BackupController extends Controller
{
    use AuthorizesRequests;
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display a listing of backups.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Backup::class);

        $filters = $request->only(['workspace_id', 'status', 'type', 'search']);
        $perPage = $request->get('per_page', 15);

        $backups = $this->backupService->getPaginatedBackups($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Backups retrieved successfully',
            'data' => BackupResource::collection($backups->items()),
            'meta' => [
                'current_page' => $backups->currentPage(),
                'last_page' => $backups->lastPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
            ]
        ]);
    }

    /**
     * Store a newly created backup.
     *
     * @param CreateBackupRequest $request
     * @return JsonResponse
     */
    public function store(CreateBackupRequest $request): JsonResponse
    {
        try {
            $backup = $this->backupService->createBackup($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => new BackupResource($backup)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified backup.
     *
     * @param Backup $backup
     * @return JsonResponse
     */
    public function show(Backup $backup): JsonResponse
    {
        $this->authorize('view', $backup);

        return response()->json([
            'success' => true,
            'message' => 'Backup retrieved successfully',
            'data' => new BackupResource($backup->load(['workspace']))
        ]);
    }

    /**
     * Update the specified backup.
     *
     * @param UpdateBackupRequest $request
     * @param Backup $backup
     * @return JsonResponse
     */
    public function update(UpdateBackupRequest $request, Backup $backup): JsonResponse
    {
        try {
            $updated = $this->backupService->updateBackup($backup, $request->validated());

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update backup'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup updated successfully',
                'data' => new BackupResource($backup->fresh(['workspace']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update backup',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified backup.
     *
     * @param Backup $backup
     * @return JsonResponse
     */
    public function destroy(Backup $backup): JsonResponse
    {
        $this->authorize('delete', $backup);

        try {
            $deleted = $this->backupService->deleteBackup($backup);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete backup'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Start backup process
     *
     * @param Backup $backup
     * @return JsonResponse
     */
    public function start(Backup $backup): JsonResponse
    {
        $this->authorize('update', $backup);

        if ($backup->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'Backup is already in progress'
            ], Response::HTTP_CONFLICT);
        }

        if ($backup->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Backup is already completed'
            ], Response::HTTP_CONFLICT);
        }

        $started = $this->backupService->startBackup($backup);

        if (!$started) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start backup'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup started successfully',
            'data' => new BackupResource($backup->fresh())
        ]);
    }

    /**
     * Complete backup process
     *
     * @param Request $request
     * @param Backup $backup
     * @return JsonResponse
     */
    public function complete(Request $request, Backup $backup): JsonResponse
    {
        $this->authorize('update', $backup);

        $request->validate([
            'size' => 'sometimes|integer|min:0'
        ]);

        if (!$backup->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'Backup is not in progress'
            ], Response::HTTP_CONFLICT);
        }

        $completed = $this->backupService->completeBackup($backup, $request->get('size'));

        if (!$completed) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete backup'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup completed successfully',
            'data' => new BackupResource($backup->fresh())
        ]);
    }

    /**
     * Mark backup as failed
     *
     * @param Request $request
     * @param Backup $backup
     * @return JsonResponse
     */
    public function fail(Request $request, Backup $backup): JsonResponse
    {
        $this->authorize('update', $backup);

        $request->validate([
            'error_message' => 'required|string|max:1000'
        ]);

        $failed = $this->backupService->failBackup($backup, $request->get('error_message'));

        if (!$failed) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark backup as failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup marked as failed',
            'data' => new BackupResource($backup->fresh())
        ]);
    }

    /**
     * Get backup statistics for workspace
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id'
        ]);

        $this->authorize('viewAny', Backup::class);

        $stats = $this->backupService->getBackupStats($request->get('workspace_id'));

        return response()->json([
            'success' => true,
            'message' => 'Backup statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}
