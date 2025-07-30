<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Audit Log Request
 * 
 * Validates data for creating new audit log entries
 */
class CreateAuditLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['required', 'string', 'max:255'],
            'auditable_type' => ['required', 'string', 'max:255'],
            'auditable_id' => ['required', 'integer'],
            'old_values' => ['nullable', 'array'],
            'new_values' => ['nullable', 'array'],
            'ip_address' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event.required' => 'The event is required.',
            'auditable_type.required' => 'The auditable type is required.',
            'auditable_id.required' => 'The auditable ID is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add current user ID if not provided
        if (!$this->has('user_id')) {
            $this->merge(['user_id' => \Auth::id()]);
        }

        // Add request metadata if not provided
        if (!$this->has('ip_address')) {
            $this->merge(['ip_address' => request()->ip()]);
        }

        if (!$this->has('user_agent')) {
            $this->merge(['user_agent' => request()->userAgent()]);
        }
    }
}