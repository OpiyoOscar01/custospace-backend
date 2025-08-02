<?php

namespace Tests\Feature;

use App\Models\Export;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Export Feature Tests
 * 
 * Tests the complete Export API functionality
 */
class ExportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->workspace->users()->attach($this->user);
        
        Storage::fake('local');
    }

    /**
     * Test user can list exports.
     */
    public function test_user_can_list_exports(): void
    {
        // Create exports for the workspace
        Export::factory(3)->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        // Create exports for other workspaces (should not be included)
        Export::factory(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/exports?workspace_id={$this->workspace->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'user_id',
                        'type',
                        'entity',
                        'filters',
                        'file_path',
                        'status',
                        'expires_at',
                        'is_in_progress',
                        'is_completed',
                        'has_failed',
                        'has_expired',
                        'is_ready_for_download',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /**
     * Test user can create export.
     */
    public function test_user_can_create_export(): void
    {
        $data = [
            'workspace_id' => $this->workspace->id,
            'type' => 'csv',
            'entity' => 'tasks',
            'filters' => [
                ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                ['field' => 'created_at', 'operator' => '>=', 'value' => '2024-01-01']
            ],
            'expires_at' => Carbon::now()->addDays(7)->toISOString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'workspace_id',
                    'user_id',
                    'type',
                    'entity',
                    'filters',
                    'status',
                    'expires_at',
                ]
            ]);

        $this->assertDatabaseHas('exports', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'csv',
            'entity' => 'tasks',
            'status' => 'pending',
        ]);
    }

    /**
     * Test user can view export.
     */
    public function test_user_can_view_export(): void
    {
        $export = Export::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/exports/{$export->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workspace_id',
                    'user_id',
                    'type',
                    'entity',
                    'status',
                    'workspace',
                    'user',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $export->id,
                    'workspace_id' => $this->workspace->id,
                    'user_id' => $this->user->id,
                ]
            ]);
    }

    /**
     * Test user can update export.
     */
    public function test_user_can_update_export(): void
    {
        $export = Export::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'status' => 'processing',
            'file_path' => 'exports/test-export.csv',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/exports/{$export->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Export updated successfully.',
                'data' => [
                    'id' => $export->id,
                    'status' => 'processing',
                    'file_path' => 'exports/test-export.csv',
                ]
            ]);

        $this->assertDatabaseHas('exports', [
            'id' => $export->id,
            'status' => 'processing',
            'file_path' => 'exports/test-export.csv',
        ]);
    }

    /**
     * Test user can delete export.
     */
    public function test_user_can_delete_export(): void
    {
        $export = Export::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/exports/{$export->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Export deleted successfully.'
            ]);

        $this->assertDatabaseMissing('exports', ['id' => $export->id]);
    }

    /**
     * Test user can download completed export.
     */
    public function test_user_can_download_completed_export(): void
    {
        // Create a test file
        Storage::put('exports/test-export.csv', 'test,data\n1,value');

        $export = Export::factory()->completed()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'file_path' => 'exports/test-export.csv',
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/exports/{$export->id}/download");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test user cannot download expired export.
     */
    public function test_user_cannot_download_expired_export(): void
    {
        $export = Export::factory()->expired()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/exports/{$export->id}/download");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Export is not ready for download or has expired.'
            ]);
    }

    /**
     * Test user can cancel export.
     */
    public function test_user_can_cancel_export(): void
    {
        $export = Export::factory()->processing()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/exports/{$export->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Export cancelled successfully.',
                'data' => [
                    'id' => $export->id,
                    'status' => 'failed',
                ]
            ]);

        $this->assertDatabaseHas('exports', [
            'id' => $export->id,
            'status' => 'failed',
        ]);
    }

    /**
     * Test user can retry failed export.
     */
    public function test_user_can_retry_failed_export(): void
    {
        $export = Export::factory()->failed()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'file_path' => 'exports/failed-export.csv',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/exports/{$export->id}/retry");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Export queued for retry.',
                'data' => [
                    'id' => $export->id,
                    'status' => 'pending',
                    'file_path' => null,
                ]
            ]);

        $this->assertDatabaseHas('exports', [
            'id' => $export->id,
            'status' => 'pending',
            'file_path' => null,
        ]);
    }

    /**
     * Test export validation fails with invalid data.
     */
    public function test_export_validation_fails_with_invalid_data(): void
    {
        $data = [
            'workspace_id' => 999999, // Non-existent workspace
            'type' => 'invalid_type',
            'entity' => 'invalid_entity',
            'expires_at' => '2023-01-01', // Past date
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id', 'type', 'entity', 'expires_at']);
    }

    /**
     * Test unauthorized user cannot access other user's exports.
     */
    public function test_unauthorized_user_cannot_access_other_users_exports(): void
    {
        $otherUser = User::factory()->create();
        $export = Export::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/exports/{$export->id}");

        $response->assertStatus(403);
    }
}
