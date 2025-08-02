<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Backup Feature Tests
 * 
 * Tests all backup API endpoints and functionality
 */
class BackupTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Assume user has access to workspace
        $this->user->workspaces()->attach($this->workspace);
    }

    /**
     * Test user can list backups
     */
    public function test_user_can_list_backups(): void
    {
        Backup::factory()
            ->count(5)
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/backups');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backups retrieved successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'name',
                        'type',
                        'status',
                        'size',
                        'created_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test user can create backup
     */
    public function test_user_can_create_backup(): void
    {
        $backupData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Backup',
            'type' => 'full',
            'path' => '/backups/test.sql',
            'disk' => 's3'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/backups', $backupData);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Backup created successfully'
            ])
            ->assertJsonPath('data.name', 'Test Backup')
            ->assertJsonPath('data.type', 'full')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('backups', [
            'name' => 'Test Backup',
            'workspace_id' => $this->workspace->id,
            'type' => 'full'
        ]);
    }

    /**
     * Test user can view specific backup
     */
    public function test_user_can_view_backup(): void
    {
        $backup = Backup::factory()
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/backups/{$backup->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup retrieved successfully'
            ])
            ->assertJsonPath('data.id', $backup->id)
            ->assertJsonPath('data.name', $backup->name);
    }

    /**
     * Test user can update backup
     */
    public function test_user_can_update_backup(): void
    {
        $backup = Backup::factory()
            ->for($this->workspace)
            ->create(['name' => 'Old Name']);

        $updateData = [
            'name' => 'Updated Backup Name',
            'size' => 5000000
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/backups/{$backup->id}", $updateData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup updated successfully'
            ])
            ->assertJsonPath('data.name', 'Updated Backup Name');

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'name' => 'Updated Backup Name',
            'size' => 5000000
        ]);
    }

    /**
     * Test user can delete backup
     */
    public function test_user_can_delete_backup(): void
    {
        $backup = Backup::factory()
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/backups/{$backup->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id
        ]);
    }

    /**
     * Test user can start backup
     */
    public function test_user_can_start_backup(): void
    {
        $backup = Backup::factory()
            ->for($this->workspace)
            ->pending()
            ->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/backups/{$backup->id}/start");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup started successfully'
            ])
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'status' => 'in_progress'
        ]);

        $backup->refresh();
        $this->assertNotNull($backup->started_at);
    }

    /**
     * Test user can complete backup
     */
    public function test_user_can_complete_backup(): void
    {
        $backup = Backup::factory()
            ->for($this->workspace)
            ->inProgress()
            ->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/backups/{$backup->id}/complete", [
                'size' => 1000000
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup completed successfully'
            ])
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'status' => 'completed',
            'size' => 1000000
        ]);

        $backup->refresh();
        $this->assertNotNull($backup->completed_at);
    }

    /**
     * Test user can mark backup as failed
     */
    public function test_user_can_fail_backup(): void
    {
        $backup = Backup::factory()
            ->for($this->workspace)
            ->inProgress()
            ->create();

        $errorMessage = 'Backup failed due to disk space';

        $response = $this->actingAs($this->user)
            ->patchJson("/api/backups/{$backup->id}/fail", [
                'error_message' => $errorMessage
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup marked as failed'
            ])
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_message', $errorMessage);

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Test user can get backup statistics
     */
    public function test_user_can_get_backup_statistics(): void
    {
        // Create various backups with different statuses
        Backup::factory()->for($this->workspace)->completed()->count(3)->create();
        Backup::factory()->for($this->workspace)->failed()->count(1)->create();
        Backup::factory()->for($this->workspace)->pending()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/backups/stats?workspace_id={$this->workspace->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backup statistics retrieved successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'completed',
                    'failed',
                    'in_progress',
                    'pending',
                    'total_size'
                ]
            ]);

        $stats = $response->json('data');
        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(3, $stats['completed']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(2, $stats['pending']);
    }

    /**
     * Test validation errors on backup creation
     */
    public function test_backup_creation_validation_errors(): void
    {
        $invalidData = [
            'name' => '', // Required
            'type' => 'invalid_type', // Invalid enum
            'workspace_id' => 99999 // Non-existent workspace
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/backups', $invalidData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'workspace_id', 'path']);
    }

    /**
     * Test unauthorized access to backup
     */
    public function test_unauthorized_backup_access(): void
    {
        $otherWorkspace = Workspace::factory()->create();
        $backup = Backup::factory()
            ->for($otherWorkspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/backups/{$backup->id}");

        $response->assertForbidden();
    }

    /**
     * Test backup filtering by status
     */
    public function test_backup_filtering_by_status(): void
    {
        Backup::factory()->for($this->workspace)->completed()->count(2)->create();
        Backup::factory()->for($this->workspace)->failed()->count(1)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/backups?status=completed');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test backup filtering by type
     */
    public function test_backup_filtering_by_type(): void
    {
        Backup::factory()->for($this->workspace)->fullBackup()->count(2)->create();
        Backup::factory()->for($this->workspace)->incrementalBackup()->count(1)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/backups?type=full');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test backup search functionality
     */
    public function test_backup_search(): void
    {
        Backup::factory()->for($this->workspace)->create(['name' => 'Important Backup']);
        Backup::factory()->for($this->workspace)->create(['name' => 'Regular Backup']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/backups?search=Important');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Important Backup', $response->json('data.0.name'));
    }
}
