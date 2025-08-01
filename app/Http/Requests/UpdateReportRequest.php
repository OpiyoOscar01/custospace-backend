<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:time_tracking,task_completion,project_progress,user_activity'],
            'filters' => ['sometimes', 'array'],
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
            'type.in' => 'The report type must be one of: time_tracking, task_completion, project_progress, user_activity.',
            'schedule_frequency.required_if' => 'Schedule frequency is required when report is scheduled.',
        ];
    }
}
