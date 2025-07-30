<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Custom Field Resource
 * 
 * Transforms custom field model into JSON API response
 */
class CustomFieldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'key' => $this->key,
            'type' => $this->type,
            'applies_to' => $this->applies_to,
            'options' => $this->options,
            'is_required' => $this->is_required,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace'),
            'custom_field_values' => CustomFieldValueResource::collection($this->whenLoaded('customFieldValues')),
            
            // Computed attributes
            'is_select_type' => $this->isSelectType(),
            'available_options' => $this->getAvailableOptions(),
        ];
    }
}
