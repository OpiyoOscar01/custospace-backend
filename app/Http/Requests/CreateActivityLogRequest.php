<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Create Activity Log Request
 * 
 * Validates data for creating new activity log entries
 */
class CreateActivityLogRequest extends FormRequest
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
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'action' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'subject_type' => ['required', 'string', 'max:255'],
            'subject_id' => ['required', 'integer'],
            'properties' => ['nullable', 'array'],
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
            'workspace_id.required' => 'The workspace is required.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'action.required' => 'The action is required.',
            'description.required' => 'The description is required.',
            'subject_type.required' => 'The subject type is required.',
            'subject_id.required' => 'The subject ID is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add current user ID if not provided
        if (!$this->has('user_id')) {
            $this->merge(['user_id' => Auth::id()]);
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