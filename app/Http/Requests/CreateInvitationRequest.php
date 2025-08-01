<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CreateInvitationRequest
 * 
 * Validates invitation creation requests
 * 
 * @package App\Http\Requests
 */
class CreateInvitationRequest extends FormRequest
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
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('invitations')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id)
                                ->where('status', 'pending');
                }),
            ],
            'role' => ['required', Rule::in(['owner', 'admin', 'member', 'viewer'])],
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
            'email.unique' => 'A pending invitation already exists for this email address in the workspace.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'team_id.exists' => 'The selected team does not exist.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default expiration if not provided (7 days from now)
        if (!$this->has('expires_at')) {
            $this->merge([
                'expires_at' => now()->addDays(7),
            ]);
        }
    }
}