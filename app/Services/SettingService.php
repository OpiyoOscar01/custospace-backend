<?php

namespace App\Services;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Setting Service
 * 
 * Handles setting business logic with caching
 */
class SettingService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'settings:';

    public function __construct(
        private SettingRepositoryInterface $repository
    ) {}

    /**
     * Get settings with filtering and pagination
     */
    public function getSettings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    /**
     * Find setting by ID
     */
    public function findSetting(int $id): ?Setting
    {
        return $this->repository->findById($id);
    }

    /**
     * Create new setting
     */
    public function createSetting(array $data): Setting
    {
        $setting = $this->repository->create($data);
        $this->clearCache($data['key'], $data['workspace_id'] ?? null);
        
        return $setting;
    }

    /**
     * Update setting
     */
    public function updateSetting(Setting $setting, array $data): Setting
    {
        $oldKey = $setting->key;
        $oldWorkspaceId = $setting->workspace_id;
        
        $updatedSetting = $this->repository->update($setting, $data);
        
        // Clear cache for both old and new keys if changed
        $this->clearCache($oldKey, $oldWorkspaceId);
        if (isset($data['key']) && $data['key'] !== $oldKey) {
            $this->clearCache($data['key'], $data['workspace_id'] ?? $oldWorkspaceId);
        }
        
        return $updatedSetting;
    }

    /**
     * Delete setting
     */
    public function deleteSetting(Setting $setting): bool
    {
        $key = $setting->key;
        $workspaceId = $setting->workspace_id;
        
        $deleted = $this->repository->delete($setting);
        
        if ($deleted) {
            $this->clearCache($key, $workspaceId);
        }
        
        return $deleted;
    }

    /**
     * Get setting value with caching
     */
    public function getValue(string $key, $default = null, ?int $workspaceId = null)
    {
        $cacheKey = $this->getCacheKey($key, $workspaceId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default, $workspaceId) {
            return $this->repository->getValue($key, $default, $workspaceId);
        });
    }

    /**
     * Set setting value with cache invalidation
     */
    public function setValue(string $key, $value, string $type = 'string', ?int $workspaceId = null): Setting
    {
        $setting = $this->repository->setValue($key, $value, $type, $workspaceId);
        $this->clearCache($key, $workspaceId);
        
        return $setting;
    }

    /**
     * Get settings for workspace with caching
     */
    public function getWorkspaceSettings(?int $workspaceId): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "workspace:{$workspaceId}:all";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($workspaceId) {
            return $this->repository->getForWorkspace($workspaceId);
        });
    }

    /**
     * Get global settings with caching
     */
    public function getGlobalSettings(): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'global:all';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->repository->getGlobal();
        });
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdateSettings(array $settings, ?int $workspaceId = null): array
    {
        $updatedSettings = [];
        
        foreach ($settings as $key => $data) {
            $value = $data['value'] ?? $data;
            $type = $data['type'] ?? 'string';
            
            $updatedSettings[$key] = $this->setValue($key, $value, $type, $workspaceId);
        }
        
        return $updatedSettings;
    }

    /**
     * Export settings as array
     */
    public function exportSettings(?int $workspaceId = null): array
    {
        $settings = $workspaceId 
            ? $this->getWorkspaceSettings($workspaceId)
            : $this->getGlobalSettings();
            
        return $settings->mapWithKeys(function (Setting $setting) {
            return [$setting->key => [
                'value' => $setting->getTypedValueAttribute(),
                'type' => $setting->type,
            ]];
        })->toArray();
    }

    /**
     * Import settings from array
     */
    public function importSettings(array $settings, ?int $workspaceId = null): int
    {
        $importedCount = 0;
        
        foreach ($settings as $key => $data) {
            $value = $data['value'] ?? $data;
            $type = $data['type'] ?? 'string';
            
            $this->setValue($key, $value, $type, $workspaceId);
            $importedCount++;
        }
        
        return $importedCount;
    }

    /**
     * Clear setting cache
     */
    private function clearCache(string $key, ?int $workspaceId): void
    {
        $cacheKey = $this->getCacheKey($key, $workspaceId);
        Cache::forget($cacheKey);
        
        // Also clear workspace/global collection cache
        $collectionCacheKey = $workspaceId 
            ? self::CACHE_PREFIX . "workspace:{$workspaceId}:all"
            : self::CACHE_PREFIX . 'global:all';
        Cache::forget($collectionCacheKey);
    }

    /**
     * Get cache key for setting
     */
    private function getCacheKey(string $key, ?int $workspaceId): string
    {
        return self::CACHE_PREFIX . ($workspaceId ? "workspace:{$workspaceId}:" : 'global:') . $key;
    }
}
