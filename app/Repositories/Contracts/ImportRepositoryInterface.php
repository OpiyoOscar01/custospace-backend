<?php

namespace App\Repositories\Contracts;

use App\Models\Import;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Import Repository Interface
 * 
 * Defines the contract for Import data access operations
 */
interface ImportRepositoryInterface
{
    /**
     * Get paginated imports for a workspace
     */
    public function getPaginatedByWorkspace(int $workspaceId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get imports by user
     */
    public function getByUser(int $userId): Collection;

    /**
     * Get imports by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Create a new import
     */
    public function create(array $data): Import;

    /**
     * Update import
     */
    public function update(Import $import, array $data): Import;

    /**
     * Find import by ID
     */
    public function findById(int $id): ?Import;

    /**
     * Delete import
     */
    public function delete(Import $import): bool;

    /**
     * Get in-progress imports
     */
    public function getInProgress(): Collection;

    /**
     * Update import progress
     */
    public function updateProgress(Import $import, int $processedRows, int $successfulRows, int $failedRows, array $errors = []): Import;
}
