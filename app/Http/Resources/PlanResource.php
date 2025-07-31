<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Plan API Resource
 * 
 * Transforms plan model data for API responses
 */
class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => [
                'amount' => $this->price,
                'formatted' => $this->formatted_price,
                'currency' => 'USD',
            ],
            'billing_cycle' => $this->billing_cycle,
            'limits' => [
                'max_users' => $this->max_users,
                'max_projects' => $this->max_projects,
                'max_storage_gb' => $this->max_storage_gb,
            ],
            'features' => $this->features ?? [],
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed attributes
            'status' => $this->is_active ? 'active' : 'inactive',
            'billing_cycle_label' => ucfirst($this->billing_cycle),
            'has_user_limit' => !is_null($this->max_users),
            'has_project_limit' => !is_null($this->max_projects),
            'has_storage_limit' => !is_null($this->max_storage_gb),
            
            // Additional metadata
            'meta' => [
                'is_free' => $this->price == 0,
                'is_enterprise' => $this->price > 100,
                'feature_count' => count($this->features ?? []),
            ],
        ];
    }
}
