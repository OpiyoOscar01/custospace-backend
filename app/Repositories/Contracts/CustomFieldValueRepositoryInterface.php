<?php

namespace App\Repositories\Contracts;

use App\Models\CustomFieldValue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Custom Field Value Repository Interface
 * 
 * Defines contract for custom field value data access operations
 */
interface CustomFieldValueRepositoryInterface
{
    /**
     * Get all custom field values with pagination
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new custom field value
     */
    public function create(array $data): CustomFieldValue;

    /**
     * Update an existing custom field value
     */
    public function update(CustomFieldValue $customFieldValue, array $data): CustomFieldValue;

    /**
     * Delete a custom field value
     */
    public function delete(CustomFieldValue $customFieldValue): bool;

    /**
     * Find custom field value by ID
     */
    public function findById(int $id): ?CustomFieldValue;

    /**
     * Get custom field values by entity
     */
    public function getByEntity(string $entityType, int $entityId): Collection;

    /**
     * Find by entity and custom field
     */
    public function findByEntityAndField(string $entityType, int $entityId, int $customFieldId): ?CustomFieldValue;
}
