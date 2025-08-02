<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Import Request
 * 
 * Validates data for creating new imports
 */
class CreateImportRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['csv', 'json', 'excel'])],
            'entity' => ['required', 'string', Rule::in(['tasks', 'projects', 'users'])],
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    $allowedMimes = [
                        'csv' => ['text/csv', 'application/csv'],
                        'excel' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                        'json' => ['application/json']
                    ];

                    if (!in_array($value->getMimeType(), $allowedMimes[$type] ?? [])) {
                        $fail("The file must be a valid {$type} file.");
                    }
                }
            ],
            'total_rows' => ['sometimes', 'integer', 'min:0'],
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
            'type.required' => 'Import type is required.',
            'type.in' => 'Import type must be csv, json, or excel.',
            'entity.required' => 'Entity type is required.',
            'entity.in' => 'Entity must be tasks, projects, or users.',
            'file.required' => 'Import file is required.',
            'file.max' => 'File size cannot exceed 10MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => \AUTH::id(),
        ]);
    }
}
