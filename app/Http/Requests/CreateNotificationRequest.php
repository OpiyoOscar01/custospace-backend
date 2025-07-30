<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Notification Request
 * 
 * Validates data for creating a new notification
 */
class CreateNotificationRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:1000'],
            'message' => ['required', 'string'],
            'data' => ['nullable', 'array'],
            'notifiable_type' => ['required', 'string', 'max:255'],
            'notifiable_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required for the notification.',
            'user_id.exists' => 'The selected user does not exist.',
            'type.required' => 'Notification type is required.',
            'title.required' => 'Notification title is required.',
            'message.required' => 'Notification message is required.',
            'notifiable_type.required' => 'Notifiable type is required.',
            'notifiable_id.required' => 'Notifiable ID is required.',
        ];
    }
}
