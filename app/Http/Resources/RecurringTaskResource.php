<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class RecurringTaskResource
 * 
 * API resource for recurring task responses
 */
class RecurringTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'frequency' => $this->frequency,
            'frequency_label' => $this->getFrequencyLabel(),
            'interval' => $this->interval,
            'days_of_week' => $this->days_of_week,
            'days_of_week_labels' => $this->getDaysOfWeekLabels(),
            'day_of_month' => $this->day_of_month,
            'next_due_date' => $this->next_due_date->toISOString(),
            'next_due_date_formatted' => $this->next_due_date->format('Y-m-d H:i:s'),
            'end_date' => $this->end_date?->toISOString(),
            'is_active' => $this->is_active,
            'is_due' => $this->isDue(),
            'recurrence_summary' => $this->getRecurrenceSummary(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'task' => $this->whenLoaded('task', function () {
                return [
                    'id' => $this->task->id,
                    'title' => $this->task->title,
                    'description' => $this->task->description,
                    'status' => $this->task->status ?? null,
                    'priority' => $this->task->priority ?? null,
                    'due_date' => $this->task->due_date ?? null,
                ];
            }),
        ];
    }

    /**
     * Get human-readable frequency label.
     */
    protected function getFrequencyLabel(): string
    {
        $labels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];

        return $labels[$this->frequency] ?? $this->frequency;
    }

    /**
     * Get human-readable days of week labels.
     */
    protected function getDaysOfWeekLabels(): ?array
    {
        if (!$this->days_of_week) {
            return null;
        }

        $dayLabels = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return array_map(function ($day) use ($dayLabels) {
            return $dayLabels[$day] ?? $day;
        }, $this->days_of_week);
    }

    /**
     * Get a human-readable recurrence summary.
     */
    protected function getRecurrenceSummary(): string
    {
        $summary = '';
        
        switch ($this->frequency) {
            case 'daily':
                $summary = $this->interval === 1 ? 'Every day' : "Every {$this->interval} days";
                break;
                
            case 'weekly':
                $summary = $this->interval === 1 ? 'Every week' : "Every {$this->interval} weeks";
                if ($this->days_of_week) {
                    $dayLabels = $this->getDaysOfWeekLabels();
                    $summary .= ' on ' . implode(', ', $dayLabels);
                }
                break;
                
            case 'monthly':
                $summary = $this->interval === 1 ? 'Every month' : "Every {$this->interval} months";
                if ($this->day_of_month) {
                    $suffix = $this->getOrdinalSuffix($this->day_of_month);
                    $summary .= " on the {$this->day_of_month}{$suffix}";
                }
                break;
                
            case 'yearly':
                $summary = $this->interval === 1 ? 'Every year' : "Every {$this->interval} years";
                break;
        }

        return $summary;
    }

    /**
     * Get ordinal suffix for day of month.
     */
    protected function getOrdinalSuffix(int $day): string
    {
        if ($day >= 11 && $day <= 13) {
            return 'th';
        }

        switch ($day % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    }
}
