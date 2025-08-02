<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Setting Repository Implementation
 * 
 * Handles setting data access operations
 */
class SettingRepository implements SettingRepositoryInterface
{
    /**
     * Get paginated settings with optional filtering
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Setting::with(['workspace']);

        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (!empty($filters['key'])) {
            $query->where('key', 'like', '%' . $filters['key'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['global']) && $filters['global']) {
            $query->whereNull('workspace_id');
        }

        return $query->orderBy('key')->paginate($perPage);
    }

    /**
     * Find setting by ID
     */
    public function findById(int $id): ?Setting
    {
        return Setting::with(['workspace'])->find($id);
    }

    /**
     * Find setting by key and workspace
     */
    public function findByKey(string $key, ?int $workspaceId = null): ?Setting
    {
        return Setting::where('key', $key)
            ->where('workspace_id', $workspaceId)
            ->first();
    }

    /**
     * Create new setting
     */
    public function create(array $data): Setting
    {
        return Setting::create($data);
    }

    /**
     * Update setting
     */
    public function update(Setting $setting, array $data): Setting
    {
        $setting->update($data);
        return $setting->fresh();
    }

    /**
     * Delete setting
     */
    public function delete(Setting $setting): bool
    {
        return $setting->delete();
    }

    /**
     * Get settings for workspace
     */
    public function getForWorkspace(?int $workspaceId): Collection
    {
        return Setting::where('workspace_id', $workspaceId)
            ->orderBy('key')
            ->get();
    }

    /**
     * Get global settings
     */
    public function getGlobal(): Collection
    {
        return Setting::whereNull('workspace_id')
            ->orderBy('key')
            ->get();
    }

    /**
     * Set setting value
     */
    public function setValue(string $key, $value, string $type = 'string', ?int $workspaceId = null): Setting
    {
        $setting = $this->findByKey($key, $workspaceId);

        if ($setting) {
            $setting->type = $type;
            $setting->setTypedValue($value);
            $setting->save();
        } else {
            $setting = $this->create([
                'workspace_id' => $workspaceId,
                'key' => $key,
                'type' => $type,
                'value' => $type === Setting::TYPE_JSON ? json_encode($value) : (string) $value,
            ]);
        }

        return $setting;
    }

    /**
     * Get setting value
     */
    public function getValue(string $key, $default = null, ?int $workspaceId = null)
    {
        $setting = $this->findByKey($key, $workspaceId);

        return $setting ? $setting->getTypedValueAttribute() : $default;
    }

    /**
     * Check if setting exists
     */
    public function exists(string $key, ?int $workspaceId = null): bool
    {
        return Setting::where('key', $key)
            ->where('workspace_id', $workspaceId)
            ->exists();
    }
}
