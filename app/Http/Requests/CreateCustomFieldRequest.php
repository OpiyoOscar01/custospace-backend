<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Custom Field Request
 * 
 * Validates data for creating a new custom field
 */
class CreateCustomFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', CustomField::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('custom_fields')
                    ->where('workspace_id', $this->workspace_id)
                    ->where('applies_to', $this->applies_to)
            ],
            'type' => ['required', Rule::in(['text', 'number', 'date', 'select', 'multiselect', 'checkbox', 'textarea', 'url', 'email'])],
            'applies_to' => ['required', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'distinct'],
            'is_required' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'key.regex' => 'The key field must contain only lowercase letters, numbers, and underscores.',
            'key.unique' => 'A custom field with this key already exists for this workspace and entity type.',
            'options.*.distinct' => 'Options must be unique.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Ensure options is required for select types
        if (in_array($this->type, ['select', 'multiselect']) && !$this->has('options')) {
            $this->merge(['options' => []]);
        }
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that select fields have options
            if (in_array($this->type, ['select', 'multiselect'])) {
                if (empty($this->options)) {
                    $validator->errors()->add('options', 'Options are required for select and multiselect fields.');
                }
            }
        });
    }
}
