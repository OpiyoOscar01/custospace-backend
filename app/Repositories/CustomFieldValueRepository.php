<?php

namespace App\Repositories;

use App\Models\CustomFieldValue;
use App\Repositories\Contracts\CustomFieldValueRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Custom Field Value Repository
 * 
 * Handles data access operations for custom field values
 */
class CustomFieldValueRepository implements CustomFieldValueRepositoryInterface
{
    /**
     * Get all custom field values with pagination
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CustomFieldValue::query()->with(['customField', 'entity']);

        if (isset($filters['custom_field_id'])) {
            $query->forCustomField($filters['custom_field_id']);
        }

        if (isset($filters['entity_type']) && isset($filters['entity_id'])) {
            $query->forEntity($filters['entity_type'], $filters['entity_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new custom field value
     */
    public function create(array $data): CustomFieldValue
    {
        return CustomFieldValue::create($data);
    }

    /**
     * Update an existing custom field value
     */
    public function update(CustomFieldValue $customFieldValue, array $data): CustomFieldValue
    {
        $customFieldValue->update($data);
        return $customFieldValue->fresh();
    }

    /**
     * Delete a custom field value
     */
    public function delete(CustomFieldValue $customFieldValue): bool
    {
        return $customFieldValue->delete();
    }

    /**
     * Find custom field value by ID
     */
    public function findById(int $id): ?CustomFieldValue
    {
        return CustomFieldValue::find($id);
    }

    /**
     * Get custom field values by entity
     */
    public function getByEntity(string $entityType, int $entityId): Collection
    {
        return CustomFieldValue::forEntity($entityType, $entityId)
            ->with(['customField'])
            ->get();
    }

    /**
     * Find by entity and custom field
     */
    public function findByEntityAndField(string $entityType, int $entityId, int $customFieldId): ?CustomFieldValue
    {
        return CustomFieldValue::forEntity($entityType, $entityId)
            ->forCustomField($customFieldId)
            ->first();
    }
}
