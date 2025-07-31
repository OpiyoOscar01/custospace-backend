<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateApiTokenRequest
 * 
 * Handles validation for creating a new API token
 */
class CreateApiTokenRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'token' => [
                'sometimes',
                'string',
                'max:80',
                'unique:api_tokens,token'
            ],
            'abilities' => [
                'sometimes',
                'array'
            ],
            'abilities.*' => [
                'string',
                'max:255'
            ],
            'expires_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now'
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
            'name.required' => 'Token name is required.',
            'token.unique' => 'This token already exists.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'name' => 'token name',
            'expires_at' => 'expiration date',
        ];
    }
}
