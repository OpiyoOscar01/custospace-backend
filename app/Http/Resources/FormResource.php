<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Form Resource
 * 
 * Transforms Form model data for API responses
 */
class FormResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'fields' => $this->fields,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed attributes
            'responses_count' => $this->whenLoaded('responses', function () {
                return $this->responses->count();
            }),
            'public_url' => route('forms.public.show', ['workspace' => $this->workspace->slug ?? '', 'form' => $this->slug]),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                    'slug' => $this->workspace->slug,
                ];
            }),
            
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            
            'recent_responses' => $this->whenLoaded('responses', function () {
                return FormResponseResource::collection(
                    $this->responses->take(5)
                );
            }),
            
            // Statistics (only when specifically requested)
            'statistics' => $this->when($request->get('include_stats'), function () {
                return [
                    'total_responses' => $this->responses()->count(),
                    'unique_users' => $this->responses()->whereNotNull('user_id')->distinct('user_id')->count(),
                    'anonymous_responses' => $this->responses()->whereNull('user_id')->count(),
                    'latest_response_at' => $this->responses()->latest()->first()?->created_at?->toISOString(),
                    'first_response_at' => $this->responses()->oldest()->first()?->created_at?->toISOString(),
                ];
            }),
        ];
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
