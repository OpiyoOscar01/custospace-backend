<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Import Request
 * 
 * Validates data for updating imports
 */
class UpdateImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $import = $this->route('import');

        return \Auth::check() &&
               \Auth::user()->can('update', $import);
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
            'total_rows' => ['sometimes', 'integer', 'min:0'],
            'processed_rows' => ['sometimes', 'integer', 'min:0'],
            'successful_rows' => ['sometimes', 'integer', 'min:0'],
            'failed_rows' => ['sometimes', 'integer', 'min:0'],
            'errors' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be pending, processing, completed, or failed.',
            'total_rows.min' => 'Total rows must be at least 0.',
            'processed_rows.min' => 'Processed rows must be at least 0.',
            'successful_rows.min' => 'Successful rows must be at least 0.',
            'failed_rows.min' => 'Failed rows must be at least 0.',
        ];
    }
}
