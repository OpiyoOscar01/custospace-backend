<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CreateUserPreferenceRequest
 * 
 * Handles validation for creating a new user preference
 */
class CreateUserPreferenceRequest extends FormRequest
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
                'exists:users,id'
            ],
            'key' => [
                'required',
                'string',
                'max:255',
                // Ensure unique combination of user_id and key
                Rule::unique('user_preferences', 'key')->where(function ($query) {
                    return $query->where('user_id', $this->user_id);
                })
            ],
            'value' => [
                'required',
                'string'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'key.required' => 'Preference key is required.',
            'key.unique' => 'This preference key already exists for the user.',
            'value.required' => 'Preference value is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'key' => 'preference key',
            'value' => 'preference value',
        ];
    }
}
