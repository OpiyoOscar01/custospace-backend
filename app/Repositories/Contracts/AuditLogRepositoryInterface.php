<?php
// app/Repositories/Contracts/AuditLogRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Audit Log Repository Interface
 * 
 * Defines the contract for audit log data operations
 */
interface AuditLogRepositoryInterface
{
    /**
     * Get paginated audit logs with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new audit log entry.
     */
    public function create(array $data): AuditLog;

    /**
     * Find audit log by ID.
     */
    public function findById(int $id): ?AuditLog;

    /**
     * Get audit logs by event type.
     */
    public function getByEvent(string $event, array $filters = []): Collection;

    /**
     * Get audit logs by user.
     */
    public function getByUser(int $userId, array $filters = []): Collection;

    /**
     * Get audit logs for a specific auditable model.
     */
    public function getByAuditable(string $auditableType, int $auditableId): Collection;

    /**
     * Delete audit logs older than specified days.
     */
    public function deleteOlderThan(int $days): int;

    /**
     * Get audit trail for a specific model.
     */
    public function getAuditTrail(string $auditableType, int $auditableId): Collection;
}