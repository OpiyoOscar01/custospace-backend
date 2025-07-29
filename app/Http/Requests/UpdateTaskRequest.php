<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by the TaskPolicy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['sometimes', 'exists:workspaces,id'],
            'project_id' => ['sometimes', 'exists:projects,id'],
            'status_id' => ['sometimes', 'exists:statuses,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'reporter_id' => ['sometimes', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:tasks,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'type' => ['sometimes', Rule::in(['task', 'bug', 'feature', 'story', 'epic'])],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'integer', 'min:0'],
            'actual_hours' => ['nullable', 'integer', 'min:0'],
            'story_points' => ['nullable', 'integer', 'min:0'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_recurring' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'milestone_ids' => ['nullable', 'array'],
            'milestone_ids.*' => ['exists:milestones,id'],
            'dependency_ids' => ['nullable', 'array'],
            'dependency_ids.*' => ['exists:tasks,id'],
            'dependency_types' => ['nullable', 'array'],
            'dependency_types.*' => [Rule::in(['blocks', 'relates_to', 'duplicates'])],
        ];
    }
}
