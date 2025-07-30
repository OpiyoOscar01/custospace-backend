<?php
// tests/Feature/ActivityLogTest.php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Activity Log Feature Tests
 * 
 * Tests all API endpoints and functionality for activity logs
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Authenticate user for all requests
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user can list activity logs.
     */
    public function test_user_can_list_activity_logs(): void
    {
        // Create test activity logs
        ActivityLog::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson('/api/activity-logs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'action',
                            'description',
                            'created_at',
                            'user',
                            'workspace',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /**
     * Test user can create activity log.
     */
    public function test_user_can_create_activity_log(): void
    {
        $activityData = [
            'workspace_id' => $this->workspace->id,
            'action' => 'created',
            'description' => 'Created a new project',
            'subject_type' => 'App\\Models\\Project',
            'subject_id' => 1,
            'properties' => ['name' => 'Test Project'],
        ];

        $response = $this->postJson('/api/activity-logs', $activityData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'action',
                        'description',
                        'workspace_id',
                        'subject_type',
                        'subject_id',
                        'properties',
                    ]
                ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'description' => 'Created a new project',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    /**
     * Test user can view single activity log.
     */
    public function test_user_can_view_activity_log(): void
    {
        $activityLog = ActivityLog::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson("/api/activity-logs/{$activityLog->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'action',
                        'description',
                        'user',
                        'workspace',
                        'subject',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $activityLog->id,
                        'action' => $activityLog->action,
                    ]
                ]);
    }

    /**
     * Test user can update activity log.
     */
    public function test_user_can_update_activity_log(): void
    {
        $activityLog = ActivityLog::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $updateData = [
            'description' => 'Updated description',
            'properties' => ['updated' => true],
        ];

        $response = $this->putJson("/api/activity-logs/{$activityLog->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'description' => 'Updated description',
                        'properties' => ['updated' => true],
                    ]
                ]);

        $this->assertDatabaseHas('activity_logs', [
            'id' => $activityLog->id,
            'description' => 'Updated description',
        ]);
    }

    /**
     * Test user can delete activity log.
     */
    public function test_user_can_delete_activity_log(): void
    {
        $activityLog = ActivityLog::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->deleteJson("/api/activity-logs/{$activityLog->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Activity log deleted successfully.'
                ]);

        $this->assertDatabaseMissing('activity_logs', [
            'id' => $activityLog->id,
        ]);
    }

    /**
     * Test get workspace activities.
     */
    public function test_get_workspace_activities(): void
    {
        ActivityLog::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson("/api/activity-logs/workspace/{$this->workspace->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'action',
                            'workspace_id',
                        ]
                    ]
                ]);
    }

    /**
     * Test get activity statistics.
     */
    public function test_get_activity_statistics(): void
    {
        ActivityLog::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'action' => 'created',
        ]);

        ActivityLog::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'action' => 'updated',
        ]);

        $response = $this->getJson('/api/activity-logs/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total_activities',
                        'activities_by_action',
                        'activities_by_day',
                    ]
                ]);
    }

    /**
     * Test bulk create activity logs.
     */
    public function test_bulk_create_activity_logs(): void
    {
        $activities = [
            [
                'workspace_id' => $this->workspace->id,
                'action' => 'created',
                'description' => 'Created first item',
                'subject_type' => 'App\\Models\\Project',
                'subject_id' => 1,
            ],
            [
                'workspace_id' => $this->workspace->id,
                'action' => 'updated',
                'description' => 'Updated second item',
                'subject_type' => 'App\\Models\\Task',
                'subject_id' => 2,
            ],
        ];

        $response = $this->postJson('/api/activity-logs/bulk', [
            'activities' => $activities,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'action',
                            'description',
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'description' => 'Created first item',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'description' => 'Updated second item',
        ]);
    }

    /**
     * Test cleanup old activity logs.
     */
    public function test_cleanup_old_activity_logs(): void
    {
        // Create old activity logs
        ActivityLog::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_at' => now()->subDays(100),
        ]);

        // Create recent activity logs
        ActivityLog::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_at' => now()->subDays(30),
        ]);

        $response = $this->postJson('/api/activity-logs/cleanup', [
            'retention_days' => 90,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'deleted_count',
                ]);

        // Should have deleted 3 old logs
        $this->assertEquals(2, ActivityLog::count());
    }

    /**
     * Test validation errors for create activity log.
     */
    public function test_create_activity_log_validation_errors(): void
    {
        $response = $this->postJson('/api/activity-logs', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'workspace_id',
                    'action',
                    'description',
                    'subject_type',
                    'subject_id',
                ]);
    }

    /**
     * Test filtering activity logs.
     */
    public function test_filter_activity_logs(): void
    {
        ActivityLog::factory()->create([
            'workspace_id' => $this->workspace->id,
            'action' => 'created',
            'user_id' => $this->user->id,
        ]);

        ActivityLog::factory()->create([
            'workspace_id' => $this->workspace->id,
            'action' => 'updated',
            'user_id' => User::factory()->create()->id,
        ]);

        // Filter by action
        $response = $this->getJson('/api/activity-logs?action=created');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('created', $data[0]['action']);

        // Filter by user
        $response = $this->getJson("/api/activity-logs?user_id={$this->user->id}");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->user->id, $data[0]['user_id']);
    }
}
