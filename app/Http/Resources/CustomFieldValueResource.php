<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Custom Field Value Resource
 * 
 * Transforms custom field value model into JSON API response
 */
class CustomFieldValueResource extends JsonResource
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
            'custom_field_id' => $this->custom_field_id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'value' => $this->value,
            'formatted_value' => $this->getFormattedValue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'custom_field' => new CustomFieldResource($this->whenLoaded('customField')),
            'entity' => $this->whenLoaded('entity'),
        ];
    }
}
