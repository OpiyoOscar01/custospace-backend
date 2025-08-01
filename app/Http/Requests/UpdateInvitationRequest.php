<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateInvitationRequest
 * 
 * Validates invitation update requests
 * 
 * @package App\Http\Requests
 */
class UpdateInvitationRequest extends FormRequest
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
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'role' => ['sometimes', Rule::in(['owner', 'admin', 'member', 'viewer'])],
            'status' => ['sometimes', Rule::in(['pending', 'accepted', 'declined', 'expired'])],
            'metadata' => ['nullable', 'array'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'team_id.exists' => 'The selected team does not exist.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }
}