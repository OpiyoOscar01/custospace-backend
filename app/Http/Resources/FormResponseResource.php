<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Form Response Resource
 * 
 * Transforms FormResponse model data for API responses
 */
class FormResponseResource extends JsonResource
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
            'form_id' => $this->form_id,
            'user_id' => $this->user_id,
            'data' => $this->formatResponseData(),
            'ip_address' => $this->when(
                $request->user()?->can('viewSensitiveData', $this->resource),
                $this->ip_address
            ),
            'user_agent' => $this->when(
                $request->user()?->can('viewSensitiveData', $this->resource),
                $this->user_agent
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'form' => $this->whenLoaded('form', function () {
                return [
                    'id' => $this->form->id,
                    'name' => $this->form->name,
                    'slug' => $this->form->slug,
                    'workspace_id' => $this->form->workspace_id,
                ];
            }),
            
            'user' => $this->whenLoaded('user', function () use ($request) {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->when(
                        $request->user()?->can('viewUserData', $this->resource),
                        $this->user->email
                    ),
                ];
            }),
            
            // Computed fields
            'is_anonymous' => is_null($this->user_id),
            'submission_type' => $this->user_id ? 'authenticated' : 'anonymous',
        ];
    }

    /**
     * Format response data with field labels
     * 
     * @return array
     */
    private function formatResponseData(): array
    {
        if (!$this->relationLoaded('form') || !$this->form) {
            return $this->data;
        }

        $formattedData = [];
        $formFields = collect($this->form->fields)->keyBy('name');

        foreach ($this->data as $fieldName => $value) {
            $field = $formFields->get($fieldName);
            
            $formattedData[$fieldName] = [
                'value' => $value,
                'label' => $field['label'] ?? $fieldName,
                'type' => $field['type'] ?? 'text',
                'formatted_value' => $this->formatFieldValue($value, $field['type'] ?? 'text'),
            ];
        }

        return $formattedData;
    }

    /**
     * Format field value based on field type
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function formatFieldValue($value, string $type)
    {
        switch ($type) {
            case 'checkbox':
                return is_array($value) ? implode(', ', $value) : $value;
            case 'date':
                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : $value;
            case 'datetime':
                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : $value;
            case 'number':
                return is_numeric($value) ? (float)$value : $value;
            default:
                return $value;
        }
    }

    /**
     * Get additional data that should be returned with the resource array.
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
