<?php

namespace App\Http\Requests;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Backup Request
 * 
 * Handles validation for updating existing backup records
 */
class UpdateBackupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $backup = $this->route('backup');
        return $this->user()->can('update', $backup);
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
                'sometimes',
                'integer',
                'exists:workspaces,id'
            ],
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'min:3'
            ],
            'type' => [
                'sometimes',
                'string',
                Rule::in(Backup::getTypes())
            ],
            'path' => [
                'sometimes',
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
            ],
            'started_at' => [
                'sometimes',
                'nullable',
                'date'
            ],
            'completed_at' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:started_at'
            ],
            'error_message' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000'
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
            'workspace_id.exists' => 'Selected workspace does not exist.',
            'name.min' => 'Backup name must be at least 3 characters.',
            'type.in' => 'Invalid backup type selected.',
            'size.min' => 'Backup size cannot be negative.',
            'completed_at.after_or_equal' => 'Completion time must be after or equal to start time.',
            'error_message.max' => 'Error message is too long (maximum 1000 characters).',
        ];
    }
}
