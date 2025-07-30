<?php

namespace Tests\Feature;

use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class RecurringTaskTest
 * 
 * Feature tests for recurring task API endpoints
 */
class RecurringTaskTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->task = Task::factory()->create();
    }

    /**
     * Test user can list recurring tasks.
     */
    public function test_user_can_list_recurring_tasks(): void
    {
        RecurringTask::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
                        ->getJson('/api/recurring-tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'task_id',
                            'frequency',
                            'interval',
                            'days_of_week',
                            'day_of_month',
                            'next_due_date',
                            'end_date',
                            'is_active',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
    }

    /**
     * Test user can create a recurring task.
     */
    public function test_user_can_create_recurring_task(): void
    {
        $recurringTaskData = [
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_WEEKLY,
            'interval' => 1,
            'days_of_week' => [1, 3, 5], // Monday, Wednesday, Friday
            'next_due_date' => now()->addWeek()->toISOString(),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/recurring-tasks', $recurringTaskData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'task_id',
                        'frequency',
                        'interval',
                        'days_of_week',
                        'next_due_date',
                        'is_active',
                    ]
                ]);

        $this->assertDatabaseHas('recurring_tasks', [
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_WEEKLY,
            'interval' => 1,
        ]);
    }

    /**
     * Test user can view a specific recurring task.
     */
    public function test_user_can_view_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->getJson("/api/recurring-tasks/{$recurringTask->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'task_id',
                        'frequency',
                        'interval',
                        'next_due_date',
                        'is_active',
                    ]
                ]);
    }

    /**
     * Test user can update a recurring task.
     */
    public function test_user_can_update_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->create([
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_DAILY,
            'interval' => 1,
        ]);

        $updateData = [
            'frequency' => RecurringTask::FREQUENCY_WEEKLY,
            'interval' => 2,
            'days_of_week' => [1, 2, 3, 4, 5], // Weekdays
        ];

        $response = $this->actingAs($this->user)
                        ->putJson("/api/recurring-tasks/{$recurringTask->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonPath('data.frequency', RecurringTask::FREQUENCY_WEEKLY)
                ->assertJsonPath('data.interval', 2);

        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $recurringTask->id,
            'frequency' => RecurringTask::FREQUENCY_WEEKLY,
            'interval' => 2,
        ]);
    }

    /**
     * Test user can delete a recurring task.
     */
    public function test_user_can_delete_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->deleteJson("/api/recurring-tasks/{$recurringTask->id}");

        $response->assertStatus(200)
                ->assertJsonPath('message', 'Recurring task deleted successfully.');

        $this->assertDatabaseMissing('recurring_tasks', [
            'id' => $recurringTask->id,
        ]);
    }

    /**
     * Test user can activate a recurring task.
     */
    public function test_user_can_activate_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->inactive()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->patchJson("/api/recurring-tasks/{$recurringTask->id}/activate");

        $response->assertStatus(200)
                ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $recurringTask->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test user can deactivate a recurring task.
     */
    public function test_user_can_deactivate_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->active()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->patchJson("/api/recurring-tasks/{$recurringTask->id}/deactivate");

        $response->assertStatus(200)
                ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $recurringTask->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test get due recurring tasks.
     */
    public function test_get_due_recurring_tasks(): void
    {
        // Create due recurring tasks
        RecurringTask::factory()->due()->count(2)->create();
        
        // Create future recurring tasks
        RecurringTask::factory()->count(1)->create([
            'next_due_date' => now()->addWeek(),
        ]);

        $response = $this->actingAs($this->user)
                        ->getJson('/api/recurring-tasks/due');

        $response->assertStatus(200);
        
        $dueTasks = $response->json('data');
        $this->assertCount(2, $dueTasks);
        
        foreach ($dueTasks as $task) {
            $this->assertTrue($task['is_due']);
        }
    }

    /**
     * Test process due recurring tasks.
     */
    public function test_process_due_recurring_tasks(): void
    {
        // Create due recurring tasks
        RecurringTask::factory()->due()->count(3)->create();

        $response = $this->actingAs($this->user)
                        ->postJson('/api/recurring-tasks/process-due');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'processed_count',
                ])
                ->assertJsonPath('processed_count', 3);
    }

    /**
     * Test update next due date.
     */
    public function test_update_next_due_date(): void
    {
        $recurringTask = RecurringTask::factory()->create([
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_WEEKLY,
            'next_due_date' => now(),
        ]);

        $originalDueDate = $recurringTask->next_due_date;

        $response = $this->actingAs($this->user)
                        ->patchJson("/api/recurring-tasks/{$recurringTask->id}/update-next-due-date");

        $response->assertStatus(200);
        
        $recurringTask->refresh();
        $this->assertNotEquals($originalDueDate->toDateString(), $recurringTask->next_due_date->toDateString());
    }

    /**
     * Test validation for invalid recurring task creation.
     */
    public function test_validation_for_invalid_recurring_task_creation(): void
    {
        $invalidData = [
            'task_id' => 999, // Non-existent task
            'frequency' => 'invalid_frequency',
            'interval' => 0, // Invalid interval
            'next_due_date' => 'invalid-date',
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/recurring-tasks', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['task_id', 'frequency', 'interval', 'next_due_date']);
    }

    /**
     * Test cannot create duplicate recurring task for same task.
     */
    public function test_cannot_create_duplicate_recurring_task_for_same_task(): void
    {
        // Create existing recurring task
        RecurringTask::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $duplicateData = [
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_DAILY,
            'interval' => 1,
            'next_due_date' => now()->addDay()->toISOString(),
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/recurring-tasks', $duplicateData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['task_id']);
    }

    /**
     * Test weekly recurring task requires days of week.
     */
    public function test_weekly_recurring_task_requires_days_of_week(): void
    {
        $weeklyData = [
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_WEEKLY,
            'interval' => 1,
            'next_due_date' => now()->addWeek()->toISOString(),
            // Missing days_of_week
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/recurring-tasks', $weeklyData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['days_of_week']);
    }

    /**
     * Test monthly recurring task requires day of month.
     */
    public function test_monthly_recurring_task_requires_day_of_month(): void
    {
        $monthlyData = [
            'task_id' => $this->task->id,
            'frequency' => RecurringTask::FREQUENCY_MONTHLY,
            'interval' => 1,
            'next_due_date' => now()->addMonth()->toISOString(),
            // Missing day_of_month
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/recurring-tasks', $monthlyData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['day_of_month']);
    }
}
