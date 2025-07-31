<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Form Request
 * 
 * Handles validation for updating existing forms
 */
class UpdateFormRequest extends FormRequest
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
        $form = $this->route('form');
        
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('forms')->where(function ($query) use ($form) {
                    return $query->where('workspace_id', $form->workspace_id);
                })->ignore($form->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'fields' => ['sometimes', 'required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'string', 'in:text,email,number,textarea,select,checkbox,radio,file'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['array'],
            'settings' => ['nullable', 'array'],
            'settings.allow_multiple_submissions' => ['boolean'],
            'settings.require_authentication' => ['boolean'],
            'settings.notification_email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'A form with this slug already exists in the workspace.',
            'fields.min' => 'At least one field is required.',
            'fields.*.name.required' => 'Each field must have a name.',
            'fields.*.type.in' => 'Invalid field type selected.',
        ];
    }
}
