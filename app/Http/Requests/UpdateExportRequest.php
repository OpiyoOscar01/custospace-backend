<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Export Request
 * 
 * Validates data for updating exports
 */
class UpdateExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $export = $this->route('export');

        return \Auth::check() &&
               \Auth::user()->can('update', $export);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['pending', 'processing', 'completed', 'failed'])],
            'file_path' => ['sometimes', 'string'],
            'expires_at' => ['sometimes', 'date', 'after:now'],
            'filters' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be pending, processing, completed, or failed.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }
}
