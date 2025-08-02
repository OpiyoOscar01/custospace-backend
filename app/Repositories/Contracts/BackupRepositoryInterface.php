<?php

namespace App\Repositories\Contracts;

use App\Models\Backup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Backup Repository Interface
 * 
 * Defines contract for backup data access operations
 */
interface BackupRepositoryInterface
{
    /**
     * Get all backups with optional filtering and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find backup by ID
     *
     * @param int $id
     * @return Backup|null
     */
    public function findById(int $id): ?Backup;

    /**
     * Create new backup
     *
     * @param array $data
     * @return Backup
     */
    public function create(array $data): Backup;

    /**
     * Update backup
     *
     * @param Backup $backup
     * @param array $data
     * @return bool
     */
    public function update(Backup $backup, array $data): bool;

    /**
     * Delete backup
     *
     * @param Backup $backup
     * @return bool
     */
    public function delete(Backup $backup): bool;

    /**
     * Get backups by workspace
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get backups by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get backups by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection;

    /**
     * Mark backup as started
     *
     * @param Backup $backup
     * @return bool
     */
    public function markAsStarted(Backup $backup): bool;

    /**
     * Mark backup as completed
     *
     * @param Backup $backup
     * @param int|null $size
     * @return bool
     */
    public function markAsCompleted(Backup $backup, ?int $size = null): bool;

    /**
     * Mark backup as failed
     *
     * @param Backup $backup
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(Backup $backup, string $errorMessage): bool;
}
