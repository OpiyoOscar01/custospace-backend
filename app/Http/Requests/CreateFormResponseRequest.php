<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Form;

/**
 * Create Form Response Request
 * 
 * Handles validation for creating form responses
 * Validates data against the form's field definitions
 */
class CreateFormResponseRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $form = Form::findOrFail($this->form_id);
        $rules = [
            'form_id' => ['required', 'integer', 'exists:forms,id'],
            'data' => ['required', 'array'],
        ];

        // Dynamic validation based on form fields
        foreach ($form->fields as $field) {
            $fieldName = "data.{$field['name']}";
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'text':
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
                    break;
                case 'select':
                case 'radio':
                    if (isset($field['options']) && is_array($field['options'])) {
                        $fieldRules[] = 'in:' . implode(',', $field['options']);
                    }
                    break;
                case 'checkbox':
                    $fieldRules[] = 'array';
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    $fieldRules[] = 'max:10240'; // 10MB
                    break;
            }

            $rules[$fieldName] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'data.required' => 'Form data is required.',
            'data.*.required' => 'This field is required.',
            'data.*.email' => 'Please enter a valid email address.',
            'data.*.numeric' => 'This field must be a number.',
            'data.*.file.max' => 'File size cannot exceed 10MB.',
        ];
    }
}
