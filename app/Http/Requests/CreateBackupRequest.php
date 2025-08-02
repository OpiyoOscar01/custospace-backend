<?php

namespace App\Http\Requests;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Backup Request
 * 
 * Handles validation for creating new backup records
 */
class CreateBackupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Backup::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id'
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Backup::getTypes())
            ],
            'path' => [
                'required',
                'string',
                'max:500'
            ],
            'disk' => [
                'sometimes',
                'string',
                'max:50',
                'in:s3,local,public'
            ],
            'size' => [
                'sometimes',
                'integer',
                'min:0'
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(Backup::getStatuses())
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'Workspace is required for backup creation.',
            'workspace_id.exists' => 'Selected workspace does not exist.',
            'name.required' => 'Backup name is required.',
            'name.min' => 'Backup name must be at least 3 characters.',
            'type.required' => 'Backup type is required.',
            'type.in' => 'Invalid backup type selected.',
            'path.required' => 'Backup path is required.',
            'size.min' => 'Backup size cannot be negative.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults if not provided
        $this->merge([
            'disk' => $this->disk ?? 's3',
            'status' => $this->status ?? Backup::STATUS_PENDING,
            'size' => $this->size ?? 0,
        ]);
    }
}
