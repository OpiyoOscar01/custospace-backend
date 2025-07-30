<?php

namespace App\Repositories;

use App\Models\CustomField;
use App\Repositories\Contracts\CustomFieldRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Custom Field Repository
 * 
 * Handles data access operations for custom fields
 */
class CustomFieldRepository implements CustomFieldRepositoryInterface
{
    /**
     * Get all custom fields with pagination
     */
    public function getAllPaginated(?int $workspaceId = null, ?string $appliesTo = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = CustomField::query()->with(['workspace']);

        if ($workspaceId) {
            $query->forWorkspace($workspaceId);
        }

        if ($appliesTo) {
            $query->appliesTo($appliesTo);
        }

        return $query->ordered()->paginate($perPage);
    }

    /**
     * Create a new custom field
     */
    public function create(array $data): CustomField
    {
        return CustomField::create($data);
    }

    /**
     * Update an existing custom field
     */
    public function update(CustomField $customField, array $data): CustomField
    {
        $customField->update($data);
        return $customField->fresh();
    }

    /**
     * Delete a custom field
     */
    public function delete(CustomField $customField): bool
    {
        return $customField->delete();
    }

    /**
     * Find custom field by ID
     */
    public function findById(int $id): ?CustomField
    {
        return CustomField::find($id);
    }

    /**
     * Get custom fields by entity type and workspace
     */
    public function getByEntity(int $workspaceId, string $appliesTo): Collection
    {
        return CustomField::forWorkspace($workspaceId)
            ->appliesTo($appliesTo)
            ->ordered()
            ->get();
    }

    /**
     * Get maximum order number for workspace and applies_to
     */
    public function getMaxOrder(int $workspaceId, string $appliesTo): int
    {
        return CustomField::forWorkspace($workspaceId)
            ->appliesTo($appliesTo)
            ->max('order') ?? 0;
    }

    /**
     * Find by key, workspace, and applies_to
     */
    public function findByKey(string $key, int $workspaceId, string $appliesTo): ?CustomField
    {
        return CustomField::where('key', $key)
            ->forWorkspace($workspaceId)
            ->appliesTo($appliesTo)
            ->first();
    }
}
