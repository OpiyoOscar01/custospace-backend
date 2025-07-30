<?php
// app/Http/Controllers/Api/AuditLogController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAuditLogRequest;
use App\Http\Requests\UpdateAuditLogRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Audit Log API Controller
 * 
 * Handles HTTP requests for audit log operations
 */
class AuditLogController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private AuditLogService $auditLogService
    ) {
        // Apply authorization policies
        $this->authorizeResource(AuditLog::class, 'audit_log');
    }

    /**
     * Display a listing of audit logs.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'user_id',
            'event',
            'auditable_type',
            'auditable_id',
            'date_from',
            'date_to'
        ]);

        $perPage = min($request->get('per_page', 15), 100);
        $auditLogs = $this->auditLogService->getPaginatedLogs($filters, $perPage);

        return AuditLogResource::collection($auditLogs);
    }

    /**
     * Store a newly created audit log.
     * 
     * @param CreateAuditLogRequest $request
     * @return AuditLogResource
     */
    public function store(CreateAuditLogRequest $request): AuditLogResource
    {
        $auditLog = $this->auditLogService->logAudit($request->validated());

        return new AuditLogResource($auditLog);
    }

    /**
     * Display the specified audit log.
     * 
     * @param AuditLog $auditLog
     * @return AuditLogResource
     */
    public function show(AuditLog $auditLog): AuditLogResource
    {
        return new AuditLogResource($auditLog->load(['user', 'auditable']));
    }

    /**
     * Update the specified audit log.
     * 
     * @param UpdateAuditLogRequest $request
     * @param AuditLog $auditLog
     * @return AuditLogResource
     */
    public function update(UpdateAuditLogRequest $request, AuditLog $auditLog): AuditLogResource
    {
        $auditLog->update($request->validated());

        return new AuditLogResource($auditLog->fresh(['user', 'auditable']));
    }

    /**
     * Remove the specified audit log.
     * 
     * @param AuditLog $auditLog
     * @return JsonResponse
     */
    public function destroy(AuditLog $auditLog): JsonResponse
    {
        $auditLog->delete();

        return response()->json([
            'message' => 'Audit log deleted successfully.'
        ]);
    }

    /**
     * Get audit trail for a specific model.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getAuditTrail(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'auditable_type' => ['required', 'string'],
            'auditable_id' => ['required', 'integer'],
        ]);

        $auditTrail = $this->auditLogService->getAuditTrail(
            $request->auditable_type,
            $request->auditable_id
        );

        return AuditLogResource::collection($auditTrail);
    }

    /**
     * Get formatted changes for an audit log.
     * 
     * @param AuditLog $auditLog
     * @return JsonResponse
     */
    public function getFormattedChanges(AuditLog $auditLog): JsonResponse
    {
        $changes = $this->auditLogService->getFormattedChanges($auditLog);

        return response()->json([
            'data' => $changes
        ]);
    }

    /**
     * Clean up old audit logs.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cleanup(Request $request): JsonResponse
    {
        $this->authorize('cleanup', AuditLog::class);

        $retentionDays = $request->get('retention_days', 365);
        $deletedCount = $this->auditLogService->cleanupOldLogs($retentionDays);

        return response()->json([
            'message' => "Cleaned up {$deletedCount} old audit logs.",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Get audit logs by event type.
     * 
     * @param Request $request
     * @param string $event
     * @return AnonymousResourceCollection
     */
    public function getByEvent(Request $request, string $event): AnonymousResourceCollection
    {
        $filters = $request->only(['limit', 'user_id', 'auditable_type']);
        $auditLogs = $this->auditLogService->getByEvent($event, $filters);

        return AuditLogResource::collection($auditLogs);
    }
}