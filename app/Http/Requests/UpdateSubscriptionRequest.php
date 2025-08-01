<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating subscriptions
 * 
 * Handles validation rules and authorization for subscription updates
 */
class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handle authorization in policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['sometimes', 'integer', 'exists:plans,id'],
            'stripe_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_price' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.exists' => 'Selected plan does not exist.',
            'quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
