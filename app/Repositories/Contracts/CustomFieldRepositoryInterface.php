<?php

namespace App\Repositories\Contracts;

use App\Models\CustomField;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Custom Field Repository Interface
 * 
 * Defines contract for custom field data access operations
 */
interface CustomFieldRepositoryInterface
{
    /**
     * Get all custom fields with pagination
     */
    public function getAllPaginated(?int $workspaceId = null, ?string $appliesTo = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new custom field
     */
    public function create(array $data): CustomField;

    /**
     * Update an existing custom field
     */
    public function update(CustomField $customField, array $data): CustomField;

    /**
     * Delete a custom field
     */
    public function delete(CustomField $customField): bool;

    /**
     * Find custom field by ID
     */
    public function findById(int $id): ?CustomField;

    /**
     * Get custom fields by entity type and workspace
     */
    public function getByEntity(int $workspaceId, string $appliesTo): Collection;

    /**
     * Get maximum order number for workspace and applies_to
     */
    public function getMaxOrder(int $workspaceId, string $appliesTo): int;

    /**
     * Find by key, workspace, and applies_to
     */
    public function findByKey(string $key, int $workspaceId, string $appliesTo): ?CustomField;
}
