<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Event Participant Request
 * 
 * Validates data for adding participants to events
 */
class CreateEventParticipantRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('event_participants')->where(function ($query) {
                    return $query->where('event_id', $this->route('event')->id);
                })
            ],
            'status' => ['sometimes', Rule::in(['pending', 'accepted', 'declined', 'tentative'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'user_id.unique' => 'This user is already a participant in this event.',
            'status.in' => 'The status must be one of: pending, accepted, declined, or tentative.',
        ];
    }
}