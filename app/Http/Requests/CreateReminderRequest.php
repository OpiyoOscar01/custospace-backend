<?php

namespace App\Http\Requests;

use App\Models\Reminder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Reminder Request
 * 
 * Validates data for creating a new reminder
 */
class CreateReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'remindable_type' => ['required', 'string', 'max:255'],
            'remindable_id' => ['required', 'integer', 'min:1'],
            'remind_at' => ['required', 'date', 'after:now'],
            'type' => ['required', 'in:' . implode(',', array_keys(Reminder::TYPES))],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required for the reminder.',
            'user_id.exists' => 'The selected user does not exist.',
            'remindable_type.required' => 'Remindable type is required.',
            'remindable_id.required' => 'Remindable ID is required.',
            'remind_at.required' => 'Reminder date and time is required.',
            'remind_at.after' => 'Reminder must be set for a future date and time.',
            'type.required' => 'Reminder type is required.',
            'type.in' => 'Invalid reminder type. Must be one of: ' . implode(', ', array_keys(Reminder::TYPES)),
        ];
    }
}
