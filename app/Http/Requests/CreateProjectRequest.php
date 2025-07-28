<?php
// app/Http/Requests/CreateProjectRequest.php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Project Request
 * 
 * Handles validation for creating new projects.
 */
class CreateProjectRequest extends FormRequest
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
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('projects')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id);
                })
            ],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => ['nullable', Rule::in(array_keys(Project::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(Project::PRIORITIES))],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_template' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace is required for the project.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'owner_id.required' => 'A project owner is required.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'name.required' => 'Project name is required.',
            'slug.unique' => 'A project with this slug already exists in the workspace.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #10B981).',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.after' => 'End date must be after the start date.',
            'progress.max' => 'Progress cannot exceed 100%.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default owner to authenticated user if not provided
        if (!$this->has('owner_id')) {
            $this->merge(['owner_id' => auth()->id()]);
        }

        // Set default workspace_id from route parameter if available
        if (!$this->has('workspace_id') && $this->route('workspace')) {
            $this->merge(['workspace_id' => $this->route('workspace')->id]);
        }
    }
}
