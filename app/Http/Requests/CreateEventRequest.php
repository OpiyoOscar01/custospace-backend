<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Event Request
 * 
 * Validates data for creating new events
 */
class CreateEventRequest extends FormRequest
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
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['meeting', 'deadline', 'reminder', 'other'])],
            'metadata' => ['nullable', 'array'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'The workspace is required.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'title.required' => 'The event title is required.',
            'start_date.required' => 'The start date is required.',
            'end_date.after' => 'The end date must be after the start date.',
            'type.in' => 'The event type must be one of: meeting, deadline, reminder, or other.',
        ];
    }
}