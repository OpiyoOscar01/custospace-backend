<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for updating invoices
 * 
 * Handles validation rules and authorization for invoice updates
 */
class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handle authorization in policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoiceId = $this->route('invoice')?->id;

        return [
            'stripe_id' => [
                'sometimes', 
                'string', 
                'max:255', 
                Rule::unique('invoices', 'stripe_id')->ignore($invoiceId)
            ],
            'number' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(Invoice::getStatuses())],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'line_items' => ['sometimes', 'nullable', 'array'],
            'line_items.*' => ['array'],
            'line_items.*.description' => ['required_with:line_items.*', 'string'],
            'line_items.*.amount' => ['required_with:line_items.*', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'stripe_id.unique' => 'Invoice with this Stripe ID already exists.',
            'amount.min' => 'Invoice amount must be at least 0.',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'status.in' => 'Invalid invoice status.',
        ];
    }
}
