<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Notification Request
 * 
 * Validates data for updating an existing notification
 */
class UpdateNotificationRequest extends FormRequest
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
            'type' => ['sometimes', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:1000'],
            'message' => ['sometimes', 'string'],
            'data' => ['nullable', 'array'],
            'notifiable_type' => ['sometimes', 'string', 'max:255'],
            'notifiable_id' => ['sometimes', 'integer', 'min:1'],
            'is_read' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'title.max' => 'Title cannot exceed 1000 characters.',
            'is_read.boolean' => 'Is read field must be true or false.',
        ];
    }
}
