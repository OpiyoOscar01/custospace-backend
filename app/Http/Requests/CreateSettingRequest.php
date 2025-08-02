<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Setting Request
 * 
 * Validates setting creation data
 */
class CreateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Setting::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'nullable',
                'integer',
                'exists:workspaces,id'
            ],
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_.-]+$/',
                Rule::unique('settings')->where(function ($query) {
                    return $query->where('workspace_id', $this->input('workspace_id'));
                })
            ],
            'value' => [
                'required'
            ],
            'type' => [
                'sometimes',
                Rule::in(Setting::getTypes())
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'key.required' => 'The setting key is required.',
            'key.regex' => 'The setting key may only contain letters, numbers, dots, dashes, and underscores.',
            'key.unique' => 'A setting with this key already exists in the specified workspace.',
            'value.required' => 'The setting value is required.',
            'type.in' => 'The type must be one of: ' . implode(', ', Setting::getTypes()),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default type if not provided
        if (!$this->has('type')) {
            $this->merge(['type' => Setting::TYPE_STRING]);
        }

        // Handle JSON type validation
        if ($this->input('type') === Setting::TYPE_JSON && is_string($this->input('value'))) {
            $decoded = json_decode($this->input('value'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['value' => $decoded]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation for JSON type
            if ($this->input('type') === Setting::TYPE_JSON) {
                $value = $this->input('value');
                if (is_string($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add('value', 'The value must be valid JSON when type is json.');
                    }
                }
            }

            // Validation for boolean type
            if ($this->input('type') === Setting::TYPE_BOOLEAN) {
                $value = $this->input('value');
                if (!is_bool($value) && !in_array($value, ['true', 'false', '1', '0', 1, 0], true)) {
                    $validator->errors()->add('value', 'The value must be a boolean when type is boolean.');
                }
            }

            // Validation for integer type
            if ($this->input('type') === Setting::TYPE_INTEGER) {
                $value = $this->input('value');
                if (!is_numeric($value)) {
                    $validator->errors()->add('value', 'The value must be numeric when type is integer.');
                }
            }
        });
    }
}
