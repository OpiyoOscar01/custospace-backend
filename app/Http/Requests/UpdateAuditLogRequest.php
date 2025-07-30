<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Audit Log Request
 * 
 * Validates data for updating audit log entries
 */
class UpdateAuditLogRequest extends FormRequest
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
            'event' => ['sometimes', 'string', 'max:255'],
            'old_values' => ['sometimes', 'nullable', 'array'],
            'new_values' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event.string' => 'The event must be a string.',
            'old_values.array' => 'The old values must be an array.',
            'new_values.array' => 'The new values must be an array.',
        ];
    }
}