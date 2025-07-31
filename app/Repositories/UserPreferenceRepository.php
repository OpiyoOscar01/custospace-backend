<?php

namespace App\Repositories;

use App\Models\UserPreference;
use App\Repositories\Contracts\UserPreferenceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class UserPreferenceRepository
 * 
 * Handles data access operations for UserPreference model
 */
class UserPreferenceRepository implements UserPreferenceRepositoryInterface
{
    /**
     * Get all user preferences with optional pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return UserPreference::with('user')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a user preference by ID
     */
    public function findById(int $id): ?UserPreference
    {
        return UserPreference::with('user')->find($id);
    }

    /**
     * Create a new user preference
     */
    public function create(array $data): UserPreference
    {
        return UserPreference::create($data);
    }

    /**
     * Update an existing user preference
     */
    public function update(UserPreference $userPreference, array $data): bool
    {
        return $userPreference->update($data);
    }

    /**
     * Delete a user preference
     */
    public function delete(UserPreference $userPreference): bool
    {
        return $userPreference->delete();
    }

    /**
     * Get preferences by user ID
     */
    public function getByUserId(int $userId): Collection
    {
        return UserPreference::where('user_id', $userId)
            ->orderBy('key')
            ->get();
    }

    /**
     * Get a specific preference by user ID and key
     */
    public function getByUserIdAndKey(int $userId, string $key): ?UserPreference
    {
        return UserPreference::where('user_id', $userId)
            ->where('key', $key)
            ->first();
    }

    /**
     * Upsert (update or insert) a user preference
     */
    public function upsert(int $userId, string $key, string $value): UserPreference
    {
        return UserPreference::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Delete all preferences for a user
     */
    public function deleteByUserId(int $userId): bool
    {
        return UserPreference::where('user_id', $userId)->delete() > 0;
    }
}
