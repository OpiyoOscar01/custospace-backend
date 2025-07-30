<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CreateTimeLogRequest
 * 
 * Handles validation for creating a new time log entry
 */
class CreateTimeLogRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'task_id' => [
                'required',
                'integer',
                Rule::exists('tasks', 'id'),
            ],
            'started_at' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            'ended_at' => [
                'nullable',
                'date',
                'after:started_at',
                'before_or_equal:now',
            ],
            'duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440', // Max 24 hours in minutes
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_billable' => [
                'boolean',
            ],
            'hourly_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999.99',
                'regex:/^\d+(\.\d{1,2})?$/', // Ensure max 2 decimal places
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'task_id.exists' => 'The selected task does not exist.',
            'started_at.before_or_equal' => 'Start time cannot be in the future.',
            'ended_at.after' => 'End time must be after start time.',
            'ended_at.before_or_equal' => 'End time cannot be in the future.',
            'duration.max' => 'Duration cannot exceed 24 hours (1440 minutes).',
            'hourly_rate.regex' => 'Hourly rate must have at most 2 decimal places.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that user doesn't have an active running time log
            if ($this->user_id && !$this->ended_at) {
                $existingRunningLog = \App\Models\TimeLog::where('user_id', $this->user_id)
                    ->whereNull('ended_at')
                    ->exists();

                if ($existingRunningLog) {
                    $validator->errors()->add('user_id', 'User already has a running time log. Please stop it first.');
                }
            }

            // Validate hourly_rate is required when is_billable is true
            if ($this->is_billable && !$this->hourly_rate) {
                $validator->errors()->add('hourly_rate', 'Hourly rate is required for billable time logs.');
            }
        });
    }
}
