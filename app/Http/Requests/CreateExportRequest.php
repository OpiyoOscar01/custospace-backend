<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Export Request
 * 
 * Validates data for creating new exports
 */
class CreateExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::check();
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
            'type' => ['required', 'string', Rule::in(['csv', 'json', 'excel', 'pdf'])],
            'entity' => ['required', 'string', Rule::in(['tasks', 'projects', 'users'])],
            'filters' => ['sometimes', 'array'],
            'filters.*.field' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string', Rule::in(['=', '!=', '>', '<', '>=', '<=', 'like', 'in', 'not_in'])],
            'filters.*.value' => ['required_with:filters'],
            'expires_at' => ['sometimes', 'date', 'after:now'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'Workspace is required.',
            'workspace_id.exists' => 'Selected workspace does not exist.',
            'type.required' => 'Export type is required.',
            'type.in' => 'Export type must be csv, json, excel, or pdf.',
            'entity.required' => 'Entity type is required.',
            'entity.in' => 'Entity must be tasks, projects, or users.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => \Auth::id(),
        ]);
    }
}
