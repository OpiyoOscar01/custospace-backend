<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in policy
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->name),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('tags', 'name')
                    ->where('workspace_id', $this->workspace_id)
            ],
            'slug' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('tags', 'slug')
                    ->where('workspace_id', $this->workspace_id)
            ],
            'color' => ['sometimes', 'string', 'max:7'],
            'description' => ['nullable', 'string'],
        ];
    }
}
