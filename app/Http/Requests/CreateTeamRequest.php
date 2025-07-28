<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the policy
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 
                'string', 
                'max:255', 
                'alpha_dash',
                Rule::unique('teams')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id);
                }),
            ],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
