<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for creating invoices
 * 
 * Handles validation rules and authorization for invoice creation
 */
class CreateInvoiceRequest extends FormRequest
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
        return [
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'stripe_id' => ['required', 'string', 'max:255', 'unique:invoices,stripe_id'],
            'number' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(Invoice::getStatuses())],
            'due_date' => ['nullable', 'date'],
            'line_items' => ['nullable', 'array'],
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
            'workspace_id.required' => 'Workspace is required.',
            'workspace_id.exists' => 'Selected workspace does not exist.',
            'stripe_id.required' => 'Stripe ID is required.',
            'stripe_id.unique' => 'Invoice with this Stripe ID already exists.',
            'number.required' => 'Invoice number is required.',
            'amount.required' => 'Invoice amount is required.',
            'amount.min' => 'Invoice amount must be at least 0.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'status.required' => 'Invoice status is required.',
            'status.in' => 'Invalid invoice status.',
        ];
    }
}
