<?php

namespace App\Http\Requests;

use App\Models\WebhookDelivery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create WebhookDelivery Request
 * 
 * Validates webhook delivery creation data
 */
class CreateWebhookDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', WebhookDelivery::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'webhook_id' => [
                'required',
                'integer',
                'exists:webhooks,id'
            ],
            'event' => [
                'required',
                'string',
                'max:255'
            ],
            'payload' => [
                'required',
                'array'
            ],
            'response_code' => [
                'nullable',
                'integer',
                'min:100',
                'max:599'
            ],
            'response_body' => [
                'nullable',
                'string'
            ],
            'status' => [
                'sometimes',
                Rule::in(WebhookDelivery::getStatuses())
            ],
            'attempts' => [
                'sometimes',
                'integer',
                'min:0',
                'max:10'
            ],
            'next_attempt_at' => [
                'nullable',
                'date',
                'after:now'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'webhook_id.required' => 'The webhook ID is required.',
            'webhook_id.exists' => 'The selected webhook does not exist.',
            'event.required' => 'The event name is required.',
            'payload.required' => 'The payload is required.',
            'payload.array' => 'The payload must be a valid JSON object.',
            'response_code.min' => 'Response code must be a valid HTTP status code.',
            'response_code.max' => 'Response code must be a valid HTTP status code.',
            'status.in' => 'The status must be one of: ' . implode(', ', WebhookDelivery::getStatuses()),
            'attempts.max' => 'Maximum number of attempts is 10.',
            'next_attempt_at.after' => 'Next attempt time must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('status')) {
            $this->merge(['status' => WebhookDelivery::STATUS_PENDING]);
        }

        if (!$this->has('attempts')) {
            $this->merge(['attempts' => 0]);
        }
    }
}
