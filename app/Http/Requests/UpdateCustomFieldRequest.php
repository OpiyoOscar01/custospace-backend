<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Custom Field Request
 * 
 * Validates data for updating an existing custom field
 */
class UpdateCustomFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customField'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customField = $this->route('customField');
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'key' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('custom_fields')
                    ->where('workspace_id', $customField->workspace_id)
                    ->where('applies_to', $customField->applies_to)
                    ->ignore($customField->id)
            ],
            'type' => ['sometimes', Rule::in(['text', 'number', 'date', 'select', 'multiselect', 'checkbox', 'textarea', 'url', 'email'])],
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
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $customField = $this->route('customField');
            $newType = $this->input('type', $customField->type);
            
            // Validate that select fields have options
            if (in_array($newType, ['select', 'multiselect'])) {
                $options = $this->input('options', $customField->options);
                if (empty($options)) {
                    $validator->errors()->add('options', 'Options are required for select and multiselect fields.');
                }
            }
        });
    }
}
