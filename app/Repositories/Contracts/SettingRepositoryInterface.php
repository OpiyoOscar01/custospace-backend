<?php

namespace App\Repositories\Contracts;

use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Setting Repository Interface
 * 
 * Defines contract for setting data access operations
 */
interface SettingRepositoryInterface
{
    /**
     * Get paginated settings with optional filtering
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find setting by ID
     */
    public function findById(int $id): ?Setting;

    /**
     * Find setting by key and workspace
     */
    public function findByKey(string $key, ?int $workspaceId = null): ?Setting;

    /**
     * Create new setting
     */
    public function create(array $data): Setting;

    /**
     * Update setting
     */
    public function update(Setting $setting, array $data): Setting;

    /**
     * Delete setting
     */
    public function delete(Setting $setting): bool;

    /**
     * Get settings for workspace
     */
    public function getForWorkspace(?int $workspaceId): Collection;

    /**
     * Get global settings
     */
    public function getGlobal(): Collection;

    /**
     * Set setting value
     */
    public function setValue(string $key, $value, string $type = 'string', ?int $workspaceId = null): Setting;

    /**
     * Get setting value
     */
    public function getValue(string $key, $default = null, ?int $workspaceId = null);

    /**
     * Check if setting exists
     */
    public function exists(string $key, ?int $workspaceId = null): bool;
}
