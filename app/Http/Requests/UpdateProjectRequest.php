<?php
namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Project Request
 * 
 * Handles validation for updating existing projects.
 */
class UpdateProjectRequest extends FormRequest
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
        $project = $this->route('project');

        return [
            'workspace_id' => ['sometimes', 'integer', Rule::exists('workspaces', 'id')],
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')],
            'owner_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('projects')->where(function ($query) use ($project) {
                    return $query->where('workspace_id', $this->workspace_id ?? $project->workspace_id);
                })->ignore($project->id)
            ],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => ['sometimes', Rule::in(array_keys(Project::STATUSES))],
            'priority' => ['sometimes', Rule::in(array_keys(Project::PRIORITIES))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'is_template' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'slug.unique' => 'A project with this slug already exists in the workspace.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #10B981).',
            'end_date.after' => 'End date must be after the start date.',
            'progress.max' => 'Progress cannot exceed 100%.',
        ];
    }
}
