<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Form Request
 * 
 * Handles validation for creating new forms
 */
class CreateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('forms')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id);
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'string', 'in:text,email,number,textarea,select,checkbox,radio,file'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['array'],
            'settings' => ['nullable', 'array'],
            'settings.allow_multiple_submissions' => ['boolean'],
            'settings.require_authentication' => ['boolean'],
            'settings.notification_email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'A form with this slug already exists in the workspace.',
            'fields.min' => 'At least one field is required.',
            'fields.*.name.required' => 'Each field must have a name.',
            'fields.*.type.in' => 'Invalid field type selected.',
        ];
    }
}
