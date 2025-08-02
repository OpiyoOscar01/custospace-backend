<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

/**
 * Setting Policy
 * 
 * Defines authorization rules for setting operations
 */
class SettingPolicy
{
    /**
     * Determine whether the user can view any settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('settings.view');
    }

    /**
     * Determine whether the user can view the setting.
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.view') && 
               $this->canAccessSetting($user, $setting);
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('settings.create');
    }

    /**
     * Determine whether the user can update the setting.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.update') && 
               $this->canAccessSetting($user, $setting);
    }

    /**
     * Determine whether the user can delete the setting.
     */
    public function delete(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.delete') && 
               $this->canAccessSetting($user, $setting) &&
               !$this->isProtectedSetting($setting);
    }

    /**
     * Determine whether the user can view setting values.
     */
    public function viewValue(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.view_values') && 
               $this->canAccessSetting($user, $setting);
    }

    /**
     * Check if user can access the setting based on workspace
     */
    private function canAccessSetting(User $user, Setting $setting): bool
    {
        // If user is admin, allow access to all settings
        if ($user->hasRole('admin')) {
            return true;
        }

        // For global settings, check if user has global access
        if (is_null($setting->workspace_id)) {
            return $user->hasPermissionTo('settings.manage_global');
        }

        // For workspace settings, check if user belongs to the workspace
        return $user->workspaces()->where('workspace_id', $setting->workspace_id)->exists();
    }

    /**
     * Check if setting is protected from deletion
     */
    private function isProtectedSetting(Setting $setting): bool
    {
        // Define protected settings that cannot be deleted
        $protectedKeys = [
            'app.name',
            'app.version',
            'system.maintenance_mode',
            'security.encryption_key',
        ];

        return in_array($setting->key, $protectedKeys);
    }
}
