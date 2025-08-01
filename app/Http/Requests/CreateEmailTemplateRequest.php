<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CreateEmailTemplateRequest
 * 
 * Validates email template creation requests
 * 
 * @package App\Http\Requests
 */
class CreateEmailTemplateRequest extends FormRequest
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
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $this->workspace_id 
                    ? Rule::unique('email_templates')->where('workspace_id', $this->workspace_id)
                    : Rule::unique('email_templates')->whereNull('workspace_id')
            ],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['system', 'custom'])],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'slug.unique' => 'A template with this slug already exists in the workspace.',
            'slug.regex' => 'The slug must only contain lowercase letters, numbers, and hyphens.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'type' => $this->input('type', 'custom'),
        ]);
    }
}