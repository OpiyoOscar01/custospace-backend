<?php

namespace App\Http\Requests;

use App\Models\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Status Request
 * 
 * Handles validation for creating new statuses.
 */
class CreateStatusRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('statuses')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id);
                })
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'type' => [
                'required',
                Rule::in(array_keys(Status::TYPES))
            ],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace is required for the status.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'name.required' => 'Status name is required.',
            'slug.unique' => 'A status with this slug already exists in the workspace.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #10B981).',
            'type.required' => 'Status type is required.',
            'type.in' => 'The selected status type is invalid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default color if not provided
        if (!$this->has('color')) {
            $this->merge(['color' => '#6B7280']);
        }

        // Set default workspace_id from route parameter if available
        if (!$this->has('workspace_id') && $this->route('workspace')) {
            $this->merge(['workspace_id' => $this->route('workspace')->id]);
        }
    }
}
