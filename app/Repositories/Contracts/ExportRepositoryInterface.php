<?php

namespace App\Repositories\Contracts;

use App\Models\Export;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Export Repository Interface
 * 
 * Defines the contract for Export data access operations
 */
interface ExportRepositoryInterface
{
    /**
     * Get paginated exports for a workspace
     */
    public function getPaginatedByWorkspace(int $workspaceId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get exports by user
     */
    public function getByUser(int $userId): Collection;

    /**
     * Get exports by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Create a new export
     */
    public function create(array $data): Export;

    /**
     * Update export
     */
    public function update(Export $export, array $data): Export;

    /**
     * Find export by ID
     */
    public function findById(int $id): ?Export;

    /**
     * Delete export
     */
    public function delete(Export $export): bool;

    /**
     * Get expired exports
     */
    public function getExpired(): Collection;

    /**
     * Clean up expired exports
     */
    public function cleanupExpired(): int;
}
