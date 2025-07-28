<?php

namespace App\Http\Requests;

use App\Models\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Status Request
 * 
 * Handles validation for updating existing statuses.
 */
class UpdateStatusRequest extends FormRequest
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
        $status = $this->route('status');

        return [
            'workspace_id' => ['sometimes', 'integer', Rule::exists('workspaces', 'id')],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('statuses')->where(function ($query) use ($status) {
                    return $query->where('workspace_id', $this->workspace_id ?? $status->workspace_id);
                })->ignore($status->id)
            ],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'type' => [
                'sometimes',
                Rule::in(array_keys(Status::TYPES))
            ],
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
            'slug.unique' => 'A status with this slug already exists in the workspace.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #10B981).',
            'type.in' => 'The selected status type is invalid.',
        ];
    }
}
