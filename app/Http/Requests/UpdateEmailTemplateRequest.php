<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateEmailTemplateRequest
 * 
 * Validates email template update requests
 * 
 * @package App\Http\Requests
 */
class UpdateEmailTemplateRequest extends FormRequest
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
        $template = $this->route('email_template');
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $template->workspace_id 
                    ? Rule::unique('email_templates')
                        ->where('workspace_id', $template->workspace_id)
                        ->ignore($template->id)
                    : Rule::unique('email_templates')
                        ->whereNull('workspace_id')
                        ->ignore($template->id)
            ],
            'subject' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'type' => ['sometimes', Rule::in(['system', 'custom'])],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'A template with this slug already exists in the workspace.',
            'slug.regex' => 'The slug must only contain lowercase letters, numbers, and hyphens.',
        ];
    }
}