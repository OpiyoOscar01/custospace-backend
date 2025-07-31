<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Plan Request
 * 
 * Validates data for updating existing subscription plans
 */
class UpdatePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('plan'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $plan = $this->route('plan');
        
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('plans', 'slug')->ignore($plan->id)
            ],
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0|max:99999.99',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
            'max_users' => 'sometimes|nullable|integer|min:1',
            'max_projects' => 'sometimes|nullable|integer|min:1',
            'max_storage_gb' => 'sometimes|nullable|integer|min:1',
            'features' => 'sometimes|nullable|array',
            'features.*' => 'string',
            'is_active' => 'sometimes|boolean',
            'is_popular' => 'sometimes|boolean',
        ];
    }
}
