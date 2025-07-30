<?php

namespace App\Services;

use App\Models\RecurringTask;
use App\Models\Task;
use App\Repositories\Contracts\RecurringTaskRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class RecurringTaskService
 * 
 * Handles business logic for recurring task operations
 */
class RecurringTaskService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected RecurringTaskRepositoryInterface $recurringTaskRepository
    ) {}

    /**
     * Get all recurring tasks with optional filtering and pagination.
     */
    public function getAllRecurringTasks(array $filters = []): LengthAwarePaginator
    {
        return $this->recurringTaskRepository->getAllWithFilters($filters);
    }

    /**
     * Create a new recurring task.
     */
    public function createRecurringTask(array $data): RecurringTask
    {
        // Ensure next_due_date is set correctly based on frequency
        if (!isset($data['next_due_date'])) {
            $data['next_due_date'] = $this->calculateInitialDueDate($data);
        }

        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        $data['interval'] = $data['interval'] ?? 1;

        return $this->recurringTaskRepository->create($data);
    }

    /**
     * Update an existing recurring task.
     */
    public function updateRecurringTask(RecurringTask $recurringTask, array $data): RecurringTask
    {
        // If frequency or interval changed, recalculate next due date
        if (isset($data['frequency']) || isset($data['interval'])) {
            $updatedData = array_merge($recurringTask->toArray(), $data);
            $data['next_due_date'] = $this->calculateNextDueDate($updatedData);
        }

        return $this->recurringTaskRepository->update($recurringTask, $data);
    }

    /**
     * Delete a recurring task.
     */
    public function deleteRecurringTask(RecurringTask $recurringTask): bool
    {
        return $this->recurringTaskRepository->delete($recurringTask);
    }

    /**
     * Activate a recurring task.
     */
    public function activateRecurringTask(RecurringTask $recurringTask): RecurringTask
    {
        return $this->recurringTaskRepository->update($recurringTask, [
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate a recurring task.
     */
    public function deactivateRecurringTask(RecurringTask $recurringTask): RecurringTask
    {
        return $this->recurringTaskRepository->update($recurringTask, [
            'is_active' => false,
        ]);
    }

    /**
     * Get due recurring tasks.
     */
    public function getDueRecurringTasks(): Collection
    {
        return $this->recurringTaskRepository->getDueRecurringTasks();
    }

    /**
     * Process due recurring tasks by creating new task instances.
     */
    public function processDueRecurringTasks(): int
    {
        $dueRecurringTasks = $this->getDueRecurringTasks();
        $processedCount = 0;

        foreach ($dueRecurringTasks as $recurringTask) {
            try {
                $this->createTaskFromRecurring($recurringTask);
                $this->updateNextDueDate($recurringTask);
                $processedCount++;
            } catch (\Exception $e) {
                // Log error but continue processing other tasks
                \Log::error("Failed to process recurring task {$recurringTask->id}: " . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * Update the next due date for a recurring task.
     */
    public function updateNextDueDate(RecurringTask $recurringTask): RecurringTask
    {
        $nextDueDate = $this->calculateNextDueDate($recurringTask->toArray());
        
        return $this->recurringTaskRepository->update($recurringTask, [
            'next_due_date' => $nextDueDate,
        ]);
    }

    /**
     * Create a new task instance from a recurring task.
     */
    protected function createTaskFromRecurring(RecurringTask $recurringTask): Task
    {
        $originalTask = $recurringTask->task;
        
        $newTaskData = [
            'title' => $originalTask->title,
            'description' => $originalTask->description,
            'status' => 'pending', // Default status for new recurring tasks
            'priority' => $originalTask->priority,
            'due_date' => $recurringTask->next_due_date,
            'assigned_to' => $originalTask->assigned_to,
            'project_id' => $originalTask->project_id,
            // Add any other fields you want to copy from the original task
        ];

        return Task::create($newTaskData);
    }

    /**
     * Calculate the initial due date for a new recurring task.
     */
    protected function calculateInitialDueDate(array $data): Carbon
    {
        $now = now();
        
        switch ($data['frequency']) {
            case RecurringTask::FREQUENCY_DAILY:
                return $now->addDays($data['interval'] ?? 1);
                
            case RecurringTask::FREQUENCY_WEEKLY:
                $dueDate = $now->addWeeks($data['interval'] ?? 1);
                if (!empty($data['days_of_week'])) {
                    // Set to the first day of the week in the array
                    $firstDay = min($data['days_of_week']);
                    $dueDate->startOfWeek()->addDays($firstDay - 1);
                }
                return $dueDate;
                
            case RecurringTask::FREQUENCY_MONTHLY:
                $dueDate = $now->addMonths($data['interval'] ?? 1);
                if (!empty($data['day_of_month'])) {
                    $dueDate->day($data['day_of_month']);
                }
                return $dueDate;
                
            case RecurringTask::FREQUENCY_YEARLY:
                return $now->addYears($data['interval'] ?? 1);
                
            default:
                return $now->addWeek();
        }
    }

    /**
     * Calculate the next due date based on current configuration.
     */
    protected function calculateNextDueDate(array $data): Carbon
    {
        $currentDueDate = Carbon::parse($data['next_due_date']);
        
        switch ($data['frequency']) {
            case RecurringTask::FREQUENCY_DAILY:
                return $currentDueDate->addDays($data['interval']);
                
            case RecurringTask::FREQUENCY_WEEKLY:
                return $currentDueDate->addWeeks($data['interval']);
                
            case RecurringTask::FREQUENCY_MONTHLY:
                $nextDate = $currentDueDate->addMonths($data['interval']);
                if (!empty($data['day_of_month'])) {
                    $nextDate->day($data['day_of_month']);
                }
                return $nextDate;
                
            case RecurringTask::FREQUENCY_YEARLY:
                return $currentDueDate->addYears($data['interval']);
                
            default:
                return $currentDueDate->addWeek();
        }
    }

    /**
     * Get recurring tasks by frequency.
     */
    public function getRecurringTasksByFrequency(string $frequency): Collection
    {
        return $this->recurringTaskRepository->getByFrequency($frequency);
    }

    /**
     * Get active recurring tasks count.
     */
    public function getActiveRecurringTasksCount(): int
    {
        return $this->recurringTaskRepository->getActiveCount();
    }

    /**
     * Get recurring tasks statistics.
     */
    public function getRecurringTasksStatistics(): array
    {
        return [
            'total_recurring_tasks' => $this->recurringTaskRepository->getTotalCount(),
            'active_recurring_tasks' => $this->recurringTaskRepository->getActiveCount(),
            'due_recurring_tasks' => $this->recurringTaskRepository->getDueCount(),
            'by_frequency' => [
                'daily' => $this->recurringTaskRepository->getCountByFrequency(RecurringTask::FREQUENCY_DAILY),
                'weekly' => $this->recurringTaskRepository->getCountByFrequency(RecurringTask::FREQUENCY_WEEKLY),
                'monthly' => $this->recurringTaskRepository->getCountByFrequency(RecurringTask::FREQUENCY_MONTHLY),
                'yearly' => $this->recurringTaskRepository->getCountByFrequency(RecurringTask::FREQUENCY_YEARLY),
            ],
        ];
    }
}
