<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Setting API Resource
 * 
 * Transforms setting data for API responses
 */
class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'key' => $this->key,
            'value' => $this->when(
                $request->user()?->can('viewValue', $this->resource),
                $this->value
            ),
            'typed_value' => $this->when(
                $request->user()?->can('viewValue', $this->resource),
                $this->getTypedValueAttribute()
            ),
            'type' => $this->type,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            // Computed attributes
            'is_global' => is_null($this->workspace_id),
            'display_value' => $this->when(
                $request->user()?->can('viewValue', $this->resource),
                $this->getDisplayValue()
            ),
            
            // Actions (if user has permissions)
            'actions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                return [
                    'can_update' => $user->can('update', $this->resource),
                    'can_delete' => $user->can('delete', $this->resource),
                ];
            }),
        ];
    }

    /**
     * Get display-friendly value based on type
     */
    private function getDisplayValue()
    {
        return match ($this->type) {
            'boolean' => $this->getTypedValueAttribute() ? 'Yes' : 'No',
            'json' => json_encode($this->getTypedValueAttribute(), JSON_PRETTY_PRINT),
            default => (string) $this->getTypedValueAttribute(),
        };
    }
}
