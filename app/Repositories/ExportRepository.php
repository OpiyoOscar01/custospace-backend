<?php

namespace App\Repositories;

use App\Models\Export;
use App\Repositories\Contracts\ExportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Export Repository
 * 
 * Handles Export data access operations
 */
class ExportRepository implements ExportRepositoryInterface
{
    /**
     * Get paginated exports for a workspace
     */
    public function getPaginatedByWorkspace(int $workspaceId, int $perPage = 15): LengthAwarePaginator
    {
        return Export::with(['user', 'workspace'])
            ->where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get exports by user
     */
    public function getByUser(int $userId): Collection
    {
        return Export::with(['workspace'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get exports by status
     */
    public function getByStatus(string $status): Collection
    {
        return Export::with(['user', 'workspace'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new export
     */
    public function create(array $data): Export
    {
        return Export::create($data);
    }

    /**
     * Update export
     */
    public function update(Export $export, array $data): Export
    {
        $export->update($data);
        return $export->fresh();
    }

    /**
     * Find export by ID
     */
    public function findById(int $id): ?Export
    {
        return Export::with(['user', 'workspace'])->find($id);
    }

    /**
     * Delete export
     */
    public function delete(Export $export): bool
    {
        // Delete associated file if exists
        if ($export->file_path && file_exists(storage_path('app/' . $export->file_path))) {
            unlink(storage_path('app/' . $export->file_path));
        }

        return $export->delete();
    }

    /**
     * Get expired exports
     */
    public function getExpired(): Collection
    {
        return Export::where('expires_at', '<', Carbon::now())
            ->where('status', 'completed')
            ->get();
    }

    /**
     * Clean up expired exports
     */
    public function cleanupExpired(): int
    {
        $expiredExports = $this->getExpired();
        $count = $expiredExports->count();

        foreach ($expiredExports as $export) {
            $this->delete($export);
        }

        return $count;
    }
}
