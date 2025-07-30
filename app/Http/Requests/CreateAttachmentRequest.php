<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for creating attachments.
 */
class CreateAttachmentRequest extends FormRequest
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
        return [
            'attachable_type' => 'required|string|max:255',
            'attachable_id' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'original_name' => 'required|string|max:255',
            'path' => 'required|string|max:500',
            'disk' => 'sometimes|string|in:public,local,s3',
            'mime_type' => 'required|string|max:255',
            'size' => 'required|integer|min:0',
            'metadata' => 'sometimes|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'attachable_type.required' => 'The attachable type is required.',
            'attachable_id.required' => 'The attachable ID is required.',
            'name.required' => 'The file name is required.',
            'original_name.required' => 'The original file name is required.',
            'path.required' => 'The file path is required.',
            'mime_type.required' => 'The MIME type is required.',
            'size.required' => 'The file size is required.',
            'size.integer' => 'The file size must be a valid number.',
        ];
    }
}
