<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'name' => [
                'nullable', 
                'string', 
                'max:255',
                Rule::requiredIf(function() {
                    return in_array($this->type, ['group', 'channel']);
                })
            ],
            'type' => ['required', 'string', Rule::in(['direct', 'group', 'channel'])],
            'is_private' => ['boolean'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ];
    }
}
