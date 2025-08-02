<?php

namespace App\Http\Requests;

use App\Models\WebhookDelivery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update WebhookDelivery Request
 * 
 * Validates webhook delivery update data
 */
class UpdateWebhookDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $webhookDelivery = $this->route('webhook_delivery');
        return $this->user()->can('update', $webhookDelivery);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'webhook_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:webhooks,id'
            ],
            'event' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'payload' => [
                'sometimes',
                'required',
                'array'
            ],
            'response_code' => [
                'sometimes',
                'nullable',
                'integer',
                'min:100',
                'max:599'
            ],
            'response_body' => [
                'sometimes',
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
                'sometimes',
                'nullable',
                'date'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'webhook_id.exists' => 'The selected webhook does not exist.',
            'event.required' => 'The event name is required.',
            'payload.required' => 'The payload is required.',
            'payload.array' => 'The payload must be a valid JSON object.',
            'response_code.min' => 'Response code must be a valid HTTP status code.',
            'response_code.max' => 'Response code must be a valid HTTP status code.',
            'status.in' => 'The status must be one of: ' . implode(', ', WebhookDelivery::getStatuses()),
            'attempts.max' => 'Maximum number of attempts is 10.',
        ];
    }
}
