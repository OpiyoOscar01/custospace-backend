<?php

namespace App\Http\Requests;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Goal Request
 * 
 * Handles validation for updating existing goal entities
 */
class UpdateGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $goal = $this->route('goal');
        return $this->user()->can('update', $goal);
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
                'sometimes',
                'integer',
                'exists:workspaces,id',
            ],
            'team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
            ],
            'owner_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
            'name' => [
                'sometimes',
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
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'team_id.exists' => 'The selected team does not exist.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'name.max' => 'The goal name may not be greater than 255 characters.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'progress.min' => 'Progress cannot be less than 0%.',
            'progress.max' => 'Progress cannot be more than 100%.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $goal = $this->route('goal');
            
            // Check if trying to change status from completed to something else
            if ($goal->status === Goal::STATUS_COMPLETED && 
                $this->has('status') && 
                $this->input('status') !== Goal::STATUS_COMPLETED) {
                $validator->errors()->add('status', 'Cannot change status of a completed goal.');
            }
        });
    }
}