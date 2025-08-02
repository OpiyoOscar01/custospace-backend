<?php

namespace App\Repositories;

use App\Models\Import;
use App\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Import Repository
 * 
 * Handles Import data access operations
 */
class ImportRepository implements ImportRepositoryInterface
{
    /**
     * Get paginated imports for a workspace
     */
    public function getPaginatedByWorkspace(int $workspaceId, int $perPage = 15): LengthAwarePaginator
    {
        return Import::with(['user', 'workspace'])
            ->where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get imports by user
     */
    public function getByUser(int $userId): Collection
    {
        return Import::with(['workspace'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get imports by status
     */
    public function getByStatus(string $status): Collection
    {
        return Import::with(['user', 'workspace'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new import
     */
    public function create(array $data): Import
    {
        return Import::create($data);
    }

    /**
     * Update import
     */
    public function update(Import $import, array $data): Import
    {
        $import->update($data);
        return $import->fresh();
    }

    /**
     * Find import by ID
     */
    public function findById(int $id): ?Import
    {
        return Import::with(['user', 'workspace'])->find($id);
    }

    /**
     * Delete import
     */
    public function delete(Import $import): bool
    {
        // Delete associated file if exists
        if ($import->file_path && file_exists(storage_path('app/' . $import->file_path))) {
            unlink(storage_path('app/' . $import->file_path));
        }

        return $import->delete();
    }

    /**
     * Get in-progress imports
     */
    public function getInProgress(): Collection
    {
        return Import::with(['user', 'workspace'])
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Update import progress
     */
    public function updateProgress(Import $import, int $processedRows, int $successfulRows, int $failedRows, array $errors = []): Import
    {
        $import->update([
            'processed_rows' => $processedRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'errors' => $errors,
        ]);

        return $import->fresh();
    }
}
