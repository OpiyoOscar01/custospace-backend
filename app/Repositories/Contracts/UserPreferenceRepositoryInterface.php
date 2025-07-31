<?php

namespace App\Repositories\Contracts;

use App\Models\UserPreference;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface UserPreferenceRepositoryInterface
 * 
 * Defines the contract for UserPreference repository operations
 */
interface UserPreferenceRepositoryInterface
{
    /**
     * Get all user preferences with optional pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a user preference by ID
     */
    public function findById(int $id): ?UserPreference;

    /**
     * Create a new user preference
     */
    public function create(array $data): UserPreference;

    /**
     * Update an existing user preference
     */
    public function update(UserPreference $userPreference, array $data): bool;

    /**
     * Delete a user preference
     */
    public function delete(UserPreference $userPreference): bool;

    /**
     * Get preferences by user ID
     */
    public function getByUserId(int $userId): Collection;

    /**
     * Get a specific preference by user ID and key
     */
    public function getByUserIdAndKey(int $userId, string $key): ?UserPreference;

    /**
     * Upsert (update or insert) a user preference
     */
    public function upsert(int $userId, string $key, string $value): UserPreference;

    /**
     * Delete all preferences for a user
     */
    public function deleteByUserId(int $userId): bool;
}
