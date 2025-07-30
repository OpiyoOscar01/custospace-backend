<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Custom Field Value Request
 * 
 * Validates data for updating an existing custom field value
 */
class UpdateCustomFieldValueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customFieldValue'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value' => ['nullable'],
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $customFieldValue = $this->route('customFieldValue');
            $customField = $customFieldValue->customField;
            
            if ($customField) {
                $this->validateValueBasedOnFieldType($validator, $customField);
            }
        });
    }

    /**
     * Validate value based on custom field type
     */
    private function validateValueBasedOnFieldType($validator, CustomField $customField): void
    {
        $value = $this->input('value');
        
        // Check if required field has value
        if ($customField->is_required && (is_null($value) || $value === '')) {
            $validator->errors()->add('value', 'This field is required.');
            return;
        }

        // Skip validation if value is empty and field is not required
        if (is_null($value) || $value === '') {
            return;
        }

        // Type-specific validation (same as CreateCustomFieldValueRequest)
        match ($customField->type) {
            'email' => $this->validateEmail($validator, $value),
            'url' => $this->validateUrl($validator, $value),
            'number' => $this->validateNumber($validator, $value),
            'date' => $this->validateDate($validator, $value),
            'select' => $this->validateSelect($validator, $value, $customField),
            'multiselect' => $this->validateMultiselect($validator, $value, $customField),
            'checkbox' => $this->validateCheckbox($validator, $value),
            default => null,
        };
    }

    // Include all validation methods from CreateCustomFieldValueRequest
    // ... (same methods as above)
}
