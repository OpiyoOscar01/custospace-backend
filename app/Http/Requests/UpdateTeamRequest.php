<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 
                'string', 
                'max:255', 
                'alpha_dash',
                Rule::unique('teams')->where(function ($query) {
                    return $query->where('workspace_id', $this->team->workspace_id);
                })->ignore($this->team),
            ],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
