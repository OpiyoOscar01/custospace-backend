<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Wiki Request - Handles validation for updating existing wikis
 */
class UpdateWikiRequest extends FormRequest
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
        $wiki = $this->route('wiki');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:wikis,id',
                function ($attribute, $value, $fail) use ($wiki) {
                    // Prevent circular reference
                    if ($value && $wiki && $value == $wiki->id) {
                        $fail('A wiki cannot be its own parent.');
                    }
                },
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('wikis', 'slug')
                    ->where(function ($query) use ($wiki) {
                        return $query->where('workspace_id', $wiki->workspace_id);
                    })
                    ->ignore($wiki->id),
            ],
            'content' => ['sometimes', 'required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],
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
            'parent_id.exists' => 'The selected parent wiki does not exist.',
            'title.required' => 'A title is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'slug.unique' => 'This slug is already taken in this workspace.',
            'slug.regex' => 'The slug format is invalid. Use lowercase letters, numbers, and hyphens only.',
            'content.required' => 'Content is required.',
        ];
    }

    /**
     * Check if content has changed to determine if revision is needed.
     */
    public function hasContentChanged(): bool
    {
        $wiki = $this->route('wiki');
        
        return $this->has('title') && $this->title !== $wiki->title ||
               $this->has('content') && $this->content !== $wiki->content;
    }
}
