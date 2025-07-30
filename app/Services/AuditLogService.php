<?php
// app/Services/AuditLogService.php

namespace App\Services;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Audit Log Service
 * 
 * Handles business logic for audit logging operations
 */
class AuditLogService
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository
    ) {}

    /**
     * Get paginated audit logs with filters.
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->auditLogRepository->getPaginated($filters, $perPage);
    }

    /**
     * Create a new audit log entry.
     */
    public function logAudit(array $data): AuditLog
    {
        // Add current user and request information if not provided
        $data = array_merge([
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $data);

        return $this->auditLogRepository->create($data);
    }

    /**
     * Log model changes (created, updated, deleted).
     */
    public function logModelChanges(
        string $event,
        $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return $this->logAudit([
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Get audit log by ID.
     */
    public function getById(int $id): ?AuditLog
    {
        return $this->auditLogRepository->findById($id);
    }

    /**
     * Get audit logs by event type.
     */
    public function getByEvent(string $event, array $filters = []): Collection
    {
        return $this->auditLogRepository->getByEvent($event, $filters);
    }

    /**
     * Get user's audit logs.
     */
    public function getUserAudits(int $userId, array $filters = []): Collection
    {
        return $this->auditLogRepository->getByUser($userId, $filters);
    }

    /**
     * Get audit logs for a specific model.
     */
    public function getModelAudits(string $auditableType, int $auditableId): Collection
    {
        return $this->auditLogRepository->getByAuditable($auditableType, $auditableId);
    }

    /**
     * Get complete audit trail for a model.
     */
    public function getAuditTrail(string $auditableType, int $auditableId): Collection
    {
        return $this->auditLogRepository->getAuditTrail($auditableType, $auditableId);
    }

    /**
     * Clean up old audit logs.
     */
    public function cleanupOldLogs(int $retentionDays = 365): int
    {
        return $this->auditLogRepository->deleteOlderThan($retentionDays);
    }

    /**
     * Compare audit log changes and get formatted differences.
     */
    public function getFormattedChanges(AuditLog $auditLog): array
    {
        $changes = [];

        if ($auditLog->old_values && $auditLog->new_values) {
            foreach ($auditLog->new_values as $field => $newValue) {
                $oldValue = $auditLog->old_values[$field] ?? null;
                
                if ($oldValue !== $newValue) {
                    $changes[$field] = [
                        'old' => $this->formatValue($oldValue),
                        'new' => $this->formatValue($newValue),
                        'field_label' => $this->getFieldLabel($field),
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Format a value for display.
     */
    private function formatValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Get human-readable field label.
     */
    private function getFieldLabel(string $field): string
    {
        // Convert snake_case to human readable
        return ucwords(str_replace('_', ' ', $field));
    }
}