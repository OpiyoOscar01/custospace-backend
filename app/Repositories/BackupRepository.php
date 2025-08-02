<?php

namespace App\Repositories;

use App\Models\Backup;
use App\Repositories\Contracts\BackupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Backup Repository Implementation
 * 
 * Handles all database operations for backup entities
 */
class BackupRepository implements BackupRepositoryInterface
{
    /**
     * Get all backups with optional filtering and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Backup::with(['workspace'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Find backup by ID
     *
     * @param int $id
     * @return Backup|null
     */
    public function findById(int $id): ?Backup
    {
        return Backup::with(['workspace'])->find($id);
    }

    /**
     * Create new backup
     *
     * @param array $data
     * @return Backup
     */
    public function create(array $data): Backup
    {
        return Backup::create($data);
    }

    /**
     * Update backup
     *
     * @param Backup $backup
     * @param array $data
     * @return bool
     */
    public function update(Backup $backup, array $data): bool
    {
        return $backup->update($data);
    }

    /**
     * Delete backup
     *
     * @param Backup $backup
     * @return bool
     */
    public function delete(Backup $backup): bool
    {
        return $backup->delete();
    }

    /**
     * Get backups by workspace
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return Backup::where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get backups by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return Backup::where('status', $status)
            ->with(['workspace'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get backups by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return Backup::where('type', $type)
            ->with(['workspace'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark backup as started
     *
     * @param Backup $backup
     * @return bool
     */
    public function markAsStarted(Backup $backup): bool
    {
        return $backup->update([
            'status' => Backup::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark backup as completed
     *
     * @param Backup $backup
     * @param int|null $size
     * @return bool
     */
    public function markAsCompleted(Backup $backup, ?int $size = null): bool
    {
        $data = [
            'status' => Backup::STATUS_COMPLETED,
            'completed_at' => now(),
        ];

        if ($size !== null) {
            $data['size'] = $size;
        }

        return $backup->update($data);
    }

    /**
     * Mark backup as failed
     *
     * @param Backup $backup
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(Backup $backup, string $errorMessage): bool
    {
        return $backup->update([
            'status' => Backup::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
