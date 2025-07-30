<?php
// app/Repositories/AuditLogRepository.php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Audit Log Repository Implementation
 * 
 * Handles all database operations for audit logs
 */
class AuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * Get paginated audit logs with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AuditLog::with(['user', 'auditable']);

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (!empty($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (!empty($filters['auditable_id'])) {
            $query->where('auditable_id', $filters['auditable_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new audit log entry.
     */
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    /**
     * Find audit log by ID.
     */
    public function findById(int $id): ?AuditLog
    {
        return AuditLog::with(['user', 'auditable'])->find($id);
    }

    /**
     * Get audit logs by event type.
     */
    public function getByEvent(string $event, array $filters = []): Collection
    {
        $query = AuditLog::byEvent($event)->with(['user', 'auditable']);

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get audit logs by user.
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = AuditLog::where('user_id', $userId)->with(['auditable']);

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get audit logs for a specific auditable model.
     */
    public function getByAuditable(string $auditableType, int $auditableId): Collection
    {
        return AuditLog::where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete audit logs older than specified days.
     */
    public function deleteOlderThan(int $days): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get audit trail for a specific model.
     */
    public function getAuditTrail(string $auditableType, int $auditableId): Collection
    {
        return AuditLog::where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}