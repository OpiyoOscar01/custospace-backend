<?php

namespace App\Services;

use App\Models\CustomFieldValue;
use App\Repositories\Contracts\CustomFieldValueRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Custom Field Value Service
 * 
 * Handles business logic for custom field value operations
 */
class CustomFieldValueService
{
    public function __construct(
        private CustomFieldValueRepositoryInterface $customFieldValueRepository
    ) {}

    /**
     * Get all custom field values with pagination
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->customFieldValueRepository->getAllPaginated($filters, $perPage);
    }

    /**
     * Create a new custom field value
     */
    public function create(array $data): CustomFieldValue
    {
        // Process value based on field type
        $data['value'] = $this->processValueForStorage($data);

        return $this->customFieldValueRepository->create($data);
    }

    /**
     * Update an existing custom field value
     */
    public function update(CustomFieldValue $customFieldValue, array $data): CustomFieldValue
    {
        // Process value based on field type
        if (isset($data['value'])) {
            $data['value'] = $this->processValueForStorage([
                'custom_field_id' => $customFieldValue->custom_field_id,
                'value' => $data['value']
            ]);
        }

        return $this->customFieldValueRepository->update($customFieldValue, $data);
    }

    /**
     * Delete a custom field value
     */
    public function delete(CustomFieldValue $customFieldValue): bool
    {
        return $this->customFieldValueRepository->delete($customFieldValue);
    }

    /**
     * Get custom field values by entity
     */
    public function getByEntity(string $entityType, int $entityId): Collection
    {
        return $this->customFieldValueRepository->getByEntity($entityType, $entityId);
    }

    /**
     * Bulk store/update custom field values for an entity
     */
    public function bulkStore(string $entityType, int $entityId, array $values): Collection
    {
        $results = collect();

        foreach ($values as $valueData) {
            $existingValue = $this->customFieldValueRepository->findByEntityAndField(
                $entityType,
                $entityId,
                $valueData['custom_field_id']
            );

            $data = [
                'custom_field_id' => $valueData['custom_field_id'],
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'value' => $valueData['value'] ?? null,
            ];

            if ($existingValue) {
                $results->push($this->update($existingValue, $data));
            } else {
                $results->push($this->create($data));
            }
        }

        return $results;
    }

    /**
     * Process value for storage based on custom field type
     */
    private function processValueForStorage(array $data): ?string
    {
        $customField = \App\Models\CustomField::find($data['custom_field_id']);
        $value = $data['value'];

        if (!$customField || is_null($value)) {
            return $value;
        }

        return match ($customField->type) {
            'multiselect' => is_array($value) ? json_encode($value) : $value,
            'checkbox' => $value ? '1' : '0',
            'date' => $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : $value,
            default => (string) $value,
        };
    }

    /**
     * Get formatted custom field values for an entity
     */
    public function getFormattedByEntity(string $entityType, int $entityId): Collection
    {
        return $this->getByEntity($entityType, $entityId)
            ->map(function (CustomFieldValue $value) {
                return [
                    'id' => $value->id,
                    'custom_field' => $value->customField,
                    'value' => $value->getFormattedValue(),
                    'raw_value' => $value->value,
                ];
            });
    }
}
