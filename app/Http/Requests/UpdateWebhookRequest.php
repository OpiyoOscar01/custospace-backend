<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url', 'max:2048'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string', 'in:task.created,task.updated,task.deleted,project.created,project.updated,user.assigned'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'retry_count' => ['integer', 'min:0', 'max:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'events.min' => 'At least one event must be selected.',
            'events.*.in' => 'Invalid event type selected.',
            'retry_count.max' => 'Retry count cannot exceed 10.',
        ];
    }
}
