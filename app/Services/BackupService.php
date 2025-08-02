<?php

namespace App\Services;

use App\Models\Backup;
use App\Repositories\Contracts\BackupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backup Service
 * 
 * Handles all business logic for backup operations
 */
class BackupService
{
    protected BackupRepositoryInterface $backupRepository;

    public function __construct(BackupRepositoryInterface $backupRepository)
    {
        $this->backupRepository = $backupRepository;
    }

    /**
     * Get paginated list of backups with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedBackups(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->backupRepository->getAllPaginated($filters, $perPage);
    }

    /**
     * Find backup by ID
     *
     * @param int $id
     * @return Backup|null
     */
    public function findBackup(int $id): ?Backup
    {
        return $this->backupRepository->findById($id);
    }

    /**
     * Create new backup
     *
     * @param array $data
     * @return Backup
     * @throws \Exception
     */
    public function createBackup(array $data): Backup
    {
        try {
            DB::beginTransaction();

            $backup = $this->backupRepository->create($data);

            Log::info('Backup created successfully', [
                'backup_id' => $backup->id,
                'workspace_id' => $backup->workspace_id,
                'type' => $backup->type
            ]);

            DB::commit();
            return $backup;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create backup', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update backup
     *
     * @param Backup $backup
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateBackup(Backup $backup, array $data): bool
    {
        try {
            DB::beginTransaction();

            $updated = $this->backupRepository->update($backup, $data);

            if ($updated) {
                Log::info('Backup updated successfully', [
                    'backup_id' => $backup->id,
                    'changes' => $data
                ]);
            }

            DB::commit();
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete backup
     *
     * @param Backup $backup
     * @return bool
     * @throws \Exception
     */
    public function deleteBackup(Backup $backup): bool
    {
        try {
            DB::beginTransaction();

            $deleted = $this->backupRepository->delete($backup);

            if ($deleted) {
                Log::info('Backup deleted successfully', [
                    'backup_id' => $backup->id
                ]);
            }

            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Start backup process
     *
     * @param Backup $backup
     * @return bool
     */
    public function startBackup(Backup $backup): bool
    {
        if ($backup->isInProgress()) {
            return false; // Already in progress
        }

        return $this->backupRepository->markAsStarted($backup);
    }

    /**
     * Complete backup process
     *
     * @param Backup $backup
     * @param int|null $size
     * @return bool
     */
    public function completeBackup(Backup $backup, ?int $size = null): bool
    {
        return $this->backupRepository->markAsCompleted($backup, $size);
    }

    /**
     * Mark backup as failed
     *
     * @param Backup $backup
     * @param string $errorMessage
     * @return bool
     */
    public function failBackup(Backup $backup, string $errorMessage): bool
    {
        return $this->backupRepository->markAsFailed($backup, $errorMessage);
    }

    /**
     * Get workspace backups
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getWorkspaceBackups(int $workspaceId): Collection
    {
        return $this->backupRepository->getByWorkspace($workspaceId);
    }

    /**
     * Get backup statistics for a workspace
     *
     * @param int $workspaceId
     * @return array
     */
    public function getBackupStats(int $workspaceId): array
    {
        $backups = $this->getWorkspaceBackups($workspaceId);

        return [
            'total' => $backups->count(),
            'completed' => $backups->where('status', Backup::STATUS_COMPLETED)->count(),
            'failed' => $backups->where('status', Backup::STATUS_FAILED)->count(),
            'in_progress' => $backups->where('status', Backup::STATUS_IN_PROGRESS)->count(),
            'pending' => $backups->where('status', Backup::STATUS_PENDING)->count(),
            'total_size' => $backups->where('status', Backup::STATUS_COMPLETED)->sum('size'),
        ];
    }
}
