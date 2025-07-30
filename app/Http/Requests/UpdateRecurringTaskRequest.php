<?php

namespace App\Http\Requests;

use App\Models\RecurringTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateRecurringTaskRequest
 * 
 * Handles validation for updating an existing recurring task configuration
 */
class UpdateRecurringTaskRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $recurringTask = $this->route('recurring_task');
        
        return [
            'task_id' => [
                'sometimes',
                'integer',
                Rule::exists('tasks', 'id'),
                Rule::unique('recurring_tasks', 'task_id')->ignore($recurringTask->id),
            ],
            'frequency' => [
                'sometimes',
                Rule::in([
                    RecurringTask::FREQUENCY_DAILY,
                    RecurringTask::FREQUENCY_WEEKLY,
                    RecurringTask::FREQUENCY_MONTHLY,
                    RecurringTask::FREQUENCY_YEARLY,
                ]),
            ],
            'interval' => [
                'sometimes',
                'integer',
                'min:1',
                'max:365', // Max interval of 365 for any frequency
            ],
            'days_of_week' => [
                'nullable',
                'array',
                'required_if:frequency,' . RecurringTask::FREQUENCY_WEEKLY,
            ],
            'days_of_week.*' => [
                'integer',
                'between:1,7', // 1 = Monday, 7 = Sunday
            ],
            'day_of_month' => [
                'nullable',
                'integer',
                'between:1,31',
                'required_if:frequency,' . RecurringTask::FREQUENCY_MONTHLY,
            ],
            'next_due_date' => [
                'sometimes',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:next_due_date',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'task_id.exists' => 'The selected task does not exist.',
            'task_id.unique' => 'Another recurring configuration already exists for this task.',
            'frequency.in' => 'Invalid frequency. Must be daily, weekly, monthly, or yearly.',
            'days_of_week.required_if' => 'Days of week are required for weekly recurring tasks.',
            'days_of_week.*.between' => 'Day of week must be between 1 (Monday) and 7 (Sunday).',
            'day_of_month.required_if' => 'Day of month is required for monthly recurring tasks.',
            'day_of_month.between' => 'Day of month must be between 1 and 31.',
            'next_due_date.after_or_equal' => 'Next due date cannot be in the past.',
            'end_date.after' => 'End date must be after the next due date.',
            'interval.max' => 'Interval cannot exceed 365.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $recurringTask = $this->route('recurring_task');
            $frequency = $this->frequency ?? $recurringTask->frequency;
            
            // Additional validation for weekly frequency
            if ($frequency === RecurringTask::FREQUENCY_WEEKLY && $this->days_of_week) {
                $daysOfWeek = array_unique($this->days_of_week);
                if (count($daysOfWeek) !== count($this->days_of_week)) {
                    $validator->errors()->add('days_of_week', 'Duplicate days of week are not allowed.');
                }
            }

            // Validate day_of_month for the current month
            if ($frequency === RecurringTask::FREQUENCY_MONTHLY && $this->day_of_month) {
                $nextDueDate = \Carbon\Carbon::parse($this->next_due_date ?? $recurringTask->next_due_date);
                $maxDayInMonth = $nextDueDate->daysInMonth;
                
                if ($this->day_of_month > $maxDayInMonth) {
                    $validator->errors()->add('day_of_month', 
                        "Day of month cannot exceed {$maxDayInMonth} for the selected month.");
                }
            }
        });
    }
}
