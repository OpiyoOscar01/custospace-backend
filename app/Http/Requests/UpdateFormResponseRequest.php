<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Form Response Request
 * 
 * Handles validation for updating form responses
 */
class UpdateFormResponseRequest extends FormRequest
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
        $formResponse = $this->route('form_response');
        $form = $formResponse->form;
        
        $rules = [
            'data' => ['sometimes', 'required', 'array'],
        ];

        // Dynamic validation based on form fields (same as create but with 'sometimes')
        foreach ($form->fields as $field) {
            $fieldName = "data.{$field['name']}";
            $fieldRules = ['sometimes'];

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
}
