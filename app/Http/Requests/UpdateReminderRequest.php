<?php

namespace App\Http\Requests;

use App\Models\Reminder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Reminder Request
 * 
 * Validates data for updating an existing reminder
 */
class UpdateReminderRequest extends FormRequest
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
            'user_id' => ['sometimes', 'exists:users,id'],
            'remindable_type' => ['sometimes', 'string', 'max:255'],
            'remindable_id' => ['sometimes', 'integer', 'min:1'],
            'remind_at' => ['sometimes', 'date'],
            'type' => ['sometimes', 'in:' . implode(',', array_keys(Reminder::TYPES))],
            'is_sent' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'remind_at.date' => 'Invalid reminder date format.',
            'type.in' => 'Invalid reminder type. Must be one of: ' . implode(', ', array_keys(Reminder::TYPES)),
            'is_sent.boolean' => 'Is sent field must be true or false.',
        ];
    }
}
