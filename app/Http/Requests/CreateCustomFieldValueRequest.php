<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Custom Field Value Request
 * 
 * Validates data for creating a new custom field value
 */
class CreateCustomFieldValueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', CustomFieldValue::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'custom_field_id' => ['required', 'exists:custom_fields,id'],
            'entity_type' => ['required', 'string', 'max:255'],
            'entity_id' => ['required', 'integer'],
            'value' => ['nullable'],
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $customField = CustomField::find($this->custom_field_id);
            
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

        // Type-specific validation
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

    private function validateEmail($validator, $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $validator->errors()->add('value', 'The value must be a valid email address.');
        }
    }

    private function validateUrl($validator, $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $validator->errors()->add('value', 'The value must be a valid URL.');
        }
    }

    private function validateNumber($validator, $value): void
    {
        if (!is_numeric($value)) {
            $validator->errors()->add('value', 'The value must be a number.');
        }
    }

    private function validateDate($validator, $value): void
    {
        if (!strtotime($value)) {
            $validator->errors()->add('value', 'The value must be a valid date.');
        }
    }

    private function validateSelect($validator, $value, CustomField $customField): void
    {
        $options = $customField->getAvailableOptions();
        if (!in_array($value, $options)) {
            $validator->errors()->add('value', 'The selected value is invalid.');
        }
    }

    private function validateMultiselect($validator, $value, CustomField $customField): void
    {
        $selectedValues = is_array($value) ? $value : json_decode($value, true);
        
        if (!is_array($selectedValues)) {
            $validator->errors()->add('value', 'The value must be an array.');
            return;
        }

        $options = $customField->getAvailableOptions();
        foreach ($selectedValues as $selectedValue) {
            if (!in_array($selectedValue, $options)) {
                $validator->errors()->add('value', "The value '{$selectedValue}' is not a valid option.");
                break;
            }
        }
    }

    private function validateCheckbox($validator, $value): void
    {
        if (!in_array($value, [true, false, 'true', 'false', '1', '0', 1, 0], true)) {
            $validator->errors()->add('value', 'The value must be a boolean.');
        }
    }
}
