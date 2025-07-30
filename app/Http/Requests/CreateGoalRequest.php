<?php

namespace App\Http\Requests;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Goal Request
 * 
 * Handles validation for creating new goal entities
 */
class CreateGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Goal::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id',
            ],
            'team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
            ],
            'owner_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(Goal::getStatuses()),
            ],
            'start_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'progress' => [
                'sometimes',
                'integer',
                'min:0',
                'max:100',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace is required for the goal.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'team_id.exists' => 'The selected team does not exist.',
            'owner_id.required' => 'An owner is required for the goal.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'name.required' => 'The goal name is required.',
            'name.max' => 'The goal name may not be greater than 255 characters.',
            'start_date.after_or_equal' => 'The start date must be today or later.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'progress.min' => 'Progress cannot be less than 0%.',
            'progress.max' => 'Progress cannot be more than 100%.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default owner to current user if not provided
        if (!$this->has('owner_id')) {
            $this->merge([
                'owner_id' => $this->user()->id,
            ]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => Goal::STATUS_DRAFT,
            ]);
        }

        // Set default progress if not provided
        if (!$this->has('progress')) {
            $this->merge([
                'progress' => 0,
            ]);
        }
    }
}