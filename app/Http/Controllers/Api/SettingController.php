<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Setting API Controller
 * 
 * Handles setting CRUD operations and custom actions
 */
class SettingController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private SettingService $settingService
    ) {
        $this->authorizeResource(Setting::class, 'setting');
    }

    /**
     * Display a listing of settings
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'workspace_id',
            'key',
            'type',
            'global'
        ]);

        $perPage = $request->integer('per_page', 15);
        $settings = $this->settingService->getSettings($filters, $perPage);

        return SettingResource::collection($settings);
    }

    /**
     * Store a newly created setting
     * 
     * @param CreateSettingRequest $request
     * @return SettingResource
     */
    public function store(CreateSettingRequest $request): SettingResource
    {
        $setting = $this->settingService->createSetting($request->validated());

        return new SettingResource($setting);
    }

    /**
     * Display the specified setting
     * 
     * @param Setting $setting
     * @return SettingResource
     */
    public function show(Setting $setting): SettingResource
    {
        return new SettingResource($setting->load(['workspace']));
    }

    /**
     * Update the specified setting
     * 
     * @param UpdateSettingRequest $request
     * @param Setting $setting
     * @return SettingResource
     */
    public function update(UpdateSettingRequest $request, Setting $setting): SettingResource
    {
        $updatedSetting = $this->settingService->updateSetting(
            $setting,
            $request->validated()
        );

        return new SettingResource($updatedSetting);
    }

    /**
     * Remove the specified setting
     * 
     * @param Setting $setting
     * @return JsonResponse
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $this->settingService->deleteSetting($setting);

        return response()->json([
            'message' => 'Setting deleted successfully'
        ]);
    }

    /**
     * Get setting value by key
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getValue(Request $request): JsonResponse
    {
        $this->authorize('view', Setting::class);

        $request->validate([
            'key' => 'required|string',
            'workspace_id' => 'nullable|integer|exists:workspaces,id',
            'default' => 'nullable'
        ]);

        $value = $this->settingService->getValue(
            $request->key,
            $request->default,
            $request->workspace_id
        );

        return response()->json([
            'key' => $request->key,
            'value' => $value,
            'workspace_id' => $request->workspace_id
        ]);
    }

    /**
     * Set setting value by key
     * 
     * @param Request $request
     * @return SettingResource
     */
    public function setValue(Request $request): SettingResource
    {
        $this->authorize('create', Setting::class);

        $request->validate([
            'key' => 'required|string|max:255|regex:/^[a-zA-Z0-9_.-]+$/',
            'value' => 'required',
            'type' => 'sometimes|in:' . implode(',', Setting::getTypes()),
            'workspace_id' => 'nullable|integer|exists:workspaces,id'
        ]);

        $setting = $this->settingService->setValue(
            $request->key,
            $request->value,
            $request->type ?? 'string',
            $request->workspace_id
        );

        return new SettingResource($setting);
    }

    /**
     * Get workspace settings
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getWorkspaceSettings(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Setting::class);

        $request->validate([
            'workspace_id' => 'nullable|integer|exists:workspaces,id'
        ]);

        $settings = $this->settingService->getWorkspaceSettings($request->workspace_id);

        return SettingResource::collection($settings);
    }

    /**
     * Get global settings
     * 
     * @return AnonymousResourceCollection
     */
    public function getGlobalSettings(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Setting::class);

        $settings = $this->settingService->getGlobalSettings();

        return SettingResource::collection($settings);
    }

    /**
     * Bulk update settings
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function bulkUpdate(Request $request): AnonymousResourceCollection
    {
        $this->authorize('create', Setting::class);

        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'array',
            'settings.*.value' => 'required',
            'settings.*.type' => 'sometimes|in:' . implode(',', Setting::getTypes()),
            'workspace_id' => 'nullable|integer|exists:workspaces,id'
        ]);

        $updatedSettings = $this->settingService->bulkUpdateSettings(
            $request->settings,
            $request->workspace_id
        );

        return SettingResource::collection(collect($updatedSettings));
    }

    /**
     * Export settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        $request->validate([
            'workspace_id' => 'nullable|integer|exists:workspaces,id'
        ]);

        $settings = $this->settingService->exportSettings($request->workspace_id);

        return response()->json([
            'data' => $settings,
            'workspace_id' => $request->workspace_id,
            'exported_at' => now()->toISOString()
        ]);
    }

    /**
     * Import settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $request->validate([
            'settings' => 'required|array',
            'workspace_id' => 'nullable|integer|exists:workspaces,id'
        ]);

        $importedCount = $this->settingService->importSettings(
            $request->settings,
            $request->workspace_id
        );

        return response()->json([
            'message' => "Successfully imported {$importedCount} settings",
            'imported_count' => $importedCount
        ]);
    }
}
