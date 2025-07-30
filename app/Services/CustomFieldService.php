<?php

namespace App\Services;

use App\Models\CustomField;
use App\Repositories\Contracts\CustomFieldRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Custom Field Service
 * 
 * Handles business logic for custom field operations
 */
class CustomFieldService
{
    public function __construct(
        private CustomFieldRepositoryInterface $customFieldRepository
    ) {}

    /**
     * Get all custom fields with pagination
     */
    public function getAllPaginated(?int $workspaceId = null, ?string $appliesTo = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customFieldRepository->getAllPaginated($workspaceId, $appliesTo, $perPage);
    }

    /**
     * Create a new custom field
     */
    public function create(array $data): CustomField
    {
        // Set default order if not provided
        if (!isset($data['order'])) {
            $data['order'] = $this->getNextOrderNumber($data['workspace_id'], $data['applies_to']);
        }

        return $this->customFieldRepository->create($data);
    }

    /**
     * Update an existing custom field
     */
    public function update(CustomField $customField, array $data): CustomField
    {
        return $this->customFieldRepository->update($customField, $data);
    }

    /**
     * Delete a custom field
     */
    public function delete(CustomField $customField): bool
    {
        return $this->customFieldRepository->delete($customField);
    }

    /**
     * Get custom fields by entity type and workspace
     */
    public function getByEntity(int $workspaceId, string $appliesTo): Collection
    {
        return $this->customFieldRepository->getByEntity($workspaceId, $appliesTo);
    }

    /**
     * Update the order of multiple custom fields
     */
    public function updateOrder(array $fields): void
    {
        foreach ($fields as $field) {
            $customField = $this->customFieldRepository->findById($field['id']);
            if ($customField) {
                $this->customFieldRepository->update($customField, ['order' => $field['order']]);
            }
        }
    }

    /**
     * Duplicate a custom field
     */
    public function duplicate(CustomField $customField): CustomField
    {
        $data = $customField->toArray();
        
        // Remove unique identifiers
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        // Modify name and key to avoid conflicts
        $data['name'] = $data['name'] . ' (Copy)';
        $data['key'] = $data['key'] . '_copy_' . time();
        $data['order'] = $this->getNextOrderNumber($customField->workspace_id, $customField->applies_to);

        return $this->customFieldRepository->create($data);
    }

    /**
     * Get the next order number for a workspace and applies_to combination
     */
    private function getNextOrderNumber(int $workspaceId, string $appliesTo): int
    {
        $maxOrder = $this->customFieldRepository->getMaxOrder($workspaceId, $appliesTo);
        return $maxOrder + 1;
    }

    /**
     * Validate custom field options for select types
     */
    public function validateOptions(string $type, ?array $options): bool
    {
        if (in_array($type, ['select', 'multiselect'])) {
            return !empty($options) && is_array($options);
        }
        
        return true;
    }

    /**
     * Get available field types
     */
    public function getAvailableFieldTypes(): array
    {
        return [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'number' => 'Number',
            'date' => 'Date',
            'email' => 'Email',
            'url' => 'URL',
            'select' => 'Select',
            'multiselect' => 'Multi-select',
            'checkbox' => 'Checkbox',
        ];
    }
}
