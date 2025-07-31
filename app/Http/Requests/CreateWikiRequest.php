<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Wiki Request - Handles validation for creating new wikis
 */
class CreateWikiRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'parent_id' => ['nullable', 'integer', 'exists:wikis,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('wikis', 'slug')->where(function ($query) {
                    return $query->where('workspace_id', $this->workspace_id);
                }),
            ],
            'content' => ['required', 'string'],
            'is_published' => ['boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata.tags' => ['nullable', 'array'],
            'metadata.tags.*' => ['string', 'max:50'],
            'metadata.description' => ['nullable', 'string', 'max:500'],
            'revision_summary' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace is required.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'parent_id.exists' => 'The selected parent wiki does not exist.',
            'title.required' => 'A title is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'slug.unique' => 'This slug is already taken in this workspace.',
            'slug.regex' => 'The slug format is invalid. Use lowercase letters, numbers, and hyphens only.',
            'content.required' => 'Content is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('is_published')) {
            $this->merge(['is_published' => false]);
        }

        // Auto-generate slug if not provided
        if (!$this->has('slug') && $this->has('title')) {
            $this->merge(['slug' => \Str::slug($this->title)]);
        }
    }

    /**
     * Get validated data with additional processing.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();
        
        // Add created_by_id from authenticated user
        $data['created_by_id'] = \Auth::id();
        
        return $data;
    }
}
