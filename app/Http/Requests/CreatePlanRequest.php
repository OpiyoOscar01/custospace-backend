<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Plan Request
 * 
 * Validates data for creating new subscription plans
 */
class CreatePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Plan::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0|max:99999.99',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_users' => 'nullable|integer|min:1',
            'max_projects' => 'nullable|integer|min:1',
            'max_storage_gb' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'is_active' => 'sometimes|boolean',
            'is_popular' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'A plan with this slug already exists.',
            'price.max' => 'The price cannot exceed $99,999.99.',
            'billing_cycle.in' => 'Billing cycle must be either monthly or yearly.',
        ];
    }
}
