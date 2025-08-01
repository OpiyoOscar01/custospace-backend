<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:time_tracking,task_completion,project_progress,user_activity'],
            'filters' => ['required', 'array'],
            'settings' => ['nullable', 'array'],
            'is_scheduled' => ['boolean'],
            'schedule_frequency' => ['nullable', 'string', 'required_if:is_scheduled,true', 'in:daily,weekly,monthly'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace must be selected.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'type.in' => 'The report type must be one of: time_tracking, task_completion, project_progress, user_activity.',
            'schedule_frequency.required_if' => 'Schedule frequency is required when report is scheduled.',
        ];
    }
}
