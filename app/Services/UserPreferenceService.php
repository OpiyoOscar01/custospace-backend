<?php

namespace App\Services;

use App\Models\UserPreference;
use App\Repositories\Contracts\UserPreferenceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class UserPreferenceService
 * 
 * Handles business logic for UserPreference operations
 */
class UserPreferenceService
{
    public function __construct(
        private UserPreferenceRepositoryInterface $userPreferenceRepository
    ) {}

    /**
     * Get paginated list of user preferences
     */
    public function getPaginatedPreferences(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userPreferenceRepository->getAllPaginated($perPage);
    }

    /**
     * Find a user preference by ID
     */
    public function findPreferenceById(int $id): ?UserPreference
    {
        return $this->userPreferenceRepository->findById($id);
    }

    /**
     * Create a new user preference
     */
    public function createPreference(array $data): UserPreference
    {
        return $this->userPreferenceRepository->create($data);
    }

    /**
     * Update an existing user preference
     */
    public function updatePreference(UserPreference $userPreference, array $data): bool
    {
        return $this->userPreferenceRepository->update($userPreference, $data);
    }

    /**
     * Delete a user preference
     */
    public function deletePreference(UserPreference $userPreference): bool
    {
        return $this->userPreferenceRepository->delete($userPreference);
    }

    /**
     * Get all preferences for a specific user
     */
    public function getUserPreferences(int $userId): Collection
    {
        return $this->userPreferenceRepository->getByUserId($userId);
    }

    /**
     * Get a specific preference value for a user
     */
    public function getUserPreference(int $userId, string $key): ?string
    {
        $preference = $this->userPreferenceRepository->getByUserIdAndKey($userId, $key);
        
        return $preference?->value;
    }

    /**
     * Set a preference for a user (create or update)
     */
    public function setUserPreference(int $userId, string $key, string $value): UserPreference
    {
        return $this->userPreferenceRepository->upsert($userId, $key, $value);
    }

    /**
     * Set multiple preferences for a user
     */
    public function setUserPreferences(int $userId, array $preferences): Collection
    {
        $results = collect();
        
        foreach ($preferences as $key => $value) {
            $results->push(
                $this->setUserPreference($userId, $key, $value)
            );
        }
        
        return $results;
    }

    /**
     * Delete all preferences for a user
     */
    public function deleteUserPreferences(int $userId): bool
    {
        return $this->userPreferenceRepository->deleteByUserId($userId);
    }
}
