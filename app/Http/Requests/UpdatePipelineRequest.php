<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Pipeline Request
 * 
 * Handles validation for updating existing pipelines.
 */
class UpdatePipelineRequest extends FormRequest
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
        $pipeline = $this->route('pipeline');

        return [
            'workspace_id' => ['sometimes', 'integer', Rule::exists('workspaces', 'id')],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('pipelines')->where(function ($query) use ($pipeline) {
                    return $query->where('workspace_id', $this->workspace_id ?? $pipeline->workspace_id);
                })->ignore($pipeline->id)
            ],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'project_id.exists' => 'The selected project does not exist.',
            'slug.unique' => 'A pipeline with this slug already exists in the workspace.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
        ];
    }
}
