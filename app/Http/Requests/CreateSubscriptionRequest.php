<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for creating subscriptions
 * 
 * Handles validation rules and authorization for subscription creation
 */
class CreateSubscriptionRequest extends FormRequest
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
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'stripe_id' => ['nullable', 'string', 'max:255'],
            'stripe_status' => ['nullable', 'string', 'max:255'],
            'stripe_price' => ['nullable', 'string', 'max:255'],
            'quantity' => ['integer', 'min:1'],
            'trial_ends_at' => ['nullable', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:trial_ends_at'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'Workspace is required.',
            'workspace_id.exists' => 'Selected workspace does not exist.',
            'plan_id.required' => 'Subscription plan is required.',
            'plan_id.exists' => 'Selected plan does not exist.',
            'quantity.min' => 'Quantity must be at least 1.',
            'trial_ends_at.after' => 'Trial end date must be in the future.',
            'ends_at.after' => 'End date must be after trial end date.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic can be added here
            if ($this->trial_ends_at && $this->ends_at) {
                if ($this->trial_ends_at >= $this->ends_at) {
                    $validator->errors()->add('ends_at', 'End date must be after trial end date.');
                }
            }
        });
    }
}
