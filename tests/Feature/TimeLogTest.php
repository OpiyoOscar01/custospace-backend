<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class TimeLogTest
 * 
 * Feature tests for time log API endpoints
 */
class TimeLogTest extends TestCase
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
     * Test user can list time logs.
     */
    public function test_user_can_list_time_logs(): void
    {
        TimeLog::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->getJson('/api/time-logs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'task_id',
                            'started_at',
                            'ended_at',
                            'duration',
                            'description',
                            'is_billable',
                            'hourly_rate',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
    }

    /**
     * Test user can create a time log.
     */
    public function test_user_can_create_time_log(): void
    {
        $timeLogData = [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'started_at' => now()->subHours(2)->toISOString(),
            'ended_at' => now()->subHour()->toISOString(),
            'description' => 'Working on feature implementation',
            'is_billable' => true,
            'hourly_rate' => 75.00,
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/time-logs', $timeLogData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'user_id',
                        'task_id',
                        'started_at',
                        'ended_at',
                        'duration',
                        'description',
                        'is_billable',
                        'hourly_rate',
                    ]
                ]);

        $this->assertDatabaseHas('time_logs', [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'is_billable' => true,
            'hourly_rate' => 75.00,
        ]);
    }

    /**
     * Test user can view a specific time log.
     */
    public function test_user_can_view_time_log(): void
    {
        $timeLog = TimeLog::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->getJson("/api/time-logs/{$timeLog->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'user_id',
                        'task_id',
                        'started_at',
                        'ended_at',
                        'duration',
                        'description',
                        'is_billable',
                        'hourly_rate',
                    ]
                ]);
    }

    /**
     * Test user can update a time log.
     */
    public function test_user_can_update_time_log(): void
    {
        $timeLog = TimeLog::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'description' => 'Original description',
        ]);

        $updateData = [
            'description' => 'Updated description',
            'is_billable' => true,
            'hourly_rate' => 80.00,
        ];

        $response = $this->actingAs($this->user)
                        ->putJson("/api/time-logs/{$timeLog->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonPath('data.description', 'Updated description')
                ->assertJsonPath('data.is_billable', true)
                ->assertJsonPath('data.hourly_rate', 80.00);

        $this->assertDatabaseHas('time_logs', [
            'id' => $timeLog->id,
            'description' => 'Updated description',
            'is_billable' => true,
            'hourly_rate' => 80.00,
        ]);
    }

    /**
     * Test user can delete a time log.
     */
    public function test_user_can_delete_time_log(): void
    {
        $timeLog = TimeLog::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->deleteJson("/api/time-logs/{$timeLog->id}");

        $response->assertStatus(200)
                ->assertJsonPath('message', 'Time log deleted successfully.');

        $this->assertDatabaseMissing('time_logs', [
            'id' => $timeLog->id,
        ]);
    }

    /**
     * Test user can start a time log.
     */
    public function test_user_can_start_time_log(): void
    {
        $startData = [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'description' => 'Starting work on task',
            'is_billable' => true,
            'hourly_rate' => 65.00,
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/time-logs/start', $startData);

        $response->assertStatus(201)
                ->assertJsonPath('data.is_running', true)
                ->assertJsonPath('data.ended_at', null);

        $this->assertDatabaseHas('time_logs', [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'ended_at' => null,
        ]);
    }

    /**
     * Test user can stop a running time log.
     */
    public function test_user_can_stop_running_time_log(): void
    {
        $timeLog = TimeLog::factory()->running()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
                        ->postJson("/api/time-logs/{$timeLog->id}/stop");

        $response->assertStatus(200)
                ->assertJsonPath('data.is_running', false);

        $timeLog->refresh();
        $this->assertNotNull($timeLog->ended_at);
        $this->assertNotNull($timeLog->duration);
    }

    /**
     * Test validation for invalid time log creation.
     */
    public function test_validation_for_invalid_time_log_creation(): void
    {
        $invalidData = [
            'user_id' => 999, // Non-existent user
            'task_id' => 999, // Non-existent task
            'started_at' => 'invalid-date',
            'hourly_rate' => -10, // Negative rate
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/time-logs', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'task_id', 'started_at', 'hourly_rate']);
    }

    /**
     * Test user cannot create multiple running time logs.
     */
    public function test_user_cannot_create_multiple_running_time_logs(): void
    {
        // Create an existing running time log
        TimeLog::factory()->running()->create([
            'user_id' => $this->user->id,
        ]);

        $newTimeLogData = [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'started_at' => now()->toISOString(),
        ];

        $response = $this->actingAs($this->user)
                        ->postJson('/api/time-logs', $newTimeLogData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test get time logs summary.
     */
    public function test_get_time_logs_summary(): void
    {
        TimeLog::factory()->billable()->count(3)->create([
            'user_id' => $this->user->id,
            'duration' => 120, // 2 hours
            'hourly_rate' => 50.00,
        ]);

        $response = $this->actingAs($this->user)
                        ->getJson('/api/time-logs/summary');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_time_logs',
                    'total_hours',
                    'billable_hours',
                    'non_billable_hours',
                    'total_earnings',
                    'average_hourly_rate',
                    'running_logs_count',
                ]);
    }

    /**
     * Test get billable time logs.
     */
    public function test_get_billable_time_logs(): void
    {
        TimeLog::factory()->billable()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        TimeLog::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'is_billable' => false,
        ]);

        $response = $this->actingAs($this->user)
                        ->getJson('/api/time-logs/billable');

        $response->assertStatus(200);
        
        $billableLogs = $response->json('data');
        $this->assertCount(2, $billableLogs);
        
        foreach ($billableLogs as $log) {
            $this->assertTrue($log['is_billable']);
        }
    }
}
