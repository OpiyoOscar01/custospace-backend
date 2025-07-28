<?php
// app/Http/Requests/CreatePipelineRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Pipeline Request
 * 
 * Handles validation for creating new pipelines.
 */
class CreatePipelineRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'integer', Rule::exists('workspaces', 'id')],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('pipelines')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id);
                })
            ],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['integer', Rule::exists('statuses', 'id')]
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace is required for the pipeline.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'project_id.exists' => 'The selected project does not exist.',
            'name.required' => 'Pipeline name is required.',
            'slug.unique' => 'A pipeline with this slug already exists in the workspace.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'statuses.*.exists' => 'One or more of the selected statuses does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default workspace_id from route parameter if available
        if (!$this->has('workspace_id') && $this->route('workspace')) {
            $this->merge(['workspace_id' => $this->route('workspace')->id]);
        }

        // Set default project_id from route parameter if available
        if (!$this->has('project_id') && $this->route('project')) {
            $this->merge(['project_id' => $this->route('project')->id]);
        }
    }
}
