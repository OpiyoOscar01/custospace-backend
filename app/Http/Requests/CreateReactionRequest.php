<?php
// app/Http/Requests/CreateReactionRequest.php

namespace App\Http\Requests;

use App\Models\Reaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Reaction Request
 * 
 * Validates data for creating new reactions
 */
class CreateReactionRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reactable_type' => ['required', 'string', 'max:255'],
            'reactable_id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(Reaction::TYPES)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reactable_type.required' => 'The reactable type is required.',
            'reactable_id.required' => 'The reactable ID is required.',
            'type.required' => 'The reaction type is required.',
            'type.in' => 'The selected reaction type is invalid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add current user ID if not provided
        if (!$this->has('user_id')) {
            $this->merge(['user_id' => \Auth::id()]);
        }
    }
}