<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Import Feature Tests
 * 
 * Tests the complete Import API functionality
 */
class ImportTest extends TestCase
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
     * Test user can list imports.
     */
    public function test_user_can_list_imports(): void
    {
        // Create imports for the workspace
        Import::factory(3)->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        // Create imports for other workspaces (should not be included)
        Import::factory(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/imports?workspace_id={$this->workspace->id}");

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
                        'file_path',
                        'total_rows',
                        'processed_rows',
                        'successful_rows',
                        'failed_rows',
                        'status',
                        'progress_percentage',
                        'is_in_progress',
                        'is_completed',
                        'has_failed',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /**
     * Test user can create import.
     */
    public function test_user_can_create_import(): void
    {
        $file = UploadedFile::fake()->create('test.csv', 100, 'text/csv');

        $data = [
            'workspace_id' => $this->workspace->id,
            'type' => 'csv',
            'entity' => 'tasks',
            'file' => $file,
            'total_rows' => 50,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/imports', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'workspace_id',
                    'user_id',
                    'type',
                    'entity',
                    'file_path',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('imports', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'csv',
            'entity' => 'tasks',
            'total_rows' => 50,
            'status' => 'pending',
        ]);

        Storage::disk('local')->assertExists('imports/' . $file->hashName());
    }

    /**
     * Test user can view import.
     */
    public function test_user_can_view_import(): void
    {
        $import = Import::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/imports/{$import->id}");

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
                    'id' => $import->id,
                    'workspace_id' => $this->workspace->id,
                    'user_id' => $this->user->id,
                ]
            ]);
    }

    /**
     * Test user can update import.
     */
    public function test_user_can_update_import(): void
    {
        $import = Import::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'processed_rows' => 0,
        ]);

        $updateData = [
            'status' => 'processing',
            'processed_rows' => 25,
            'successful_rows' => 20,
            'failed_rows' => 5,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/imports/{$import->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Import updated successfully.',
                'data' => [
                    'id' => $import->id,
                    'status' => 'processing',
                    'processed_rows' => 25,
                    'successful_rows' => 20,
                    'failed_rows' => 5,
                ]
            ]);

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'status' => 'processing',
            'processed_rows' => 25,
            'successful_rows' => 20,
            'failed_rows' => 5,
        ]);
    }

    /**
     * Test user can delete import.
     */
    public function test_user_can_delete_import(): void
    {
        $import = Import::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Import deleted successfully.'
            ]);

        $this->assertDatabaseMissing('imports', ['id' => $import->id]);
    }

    /**
     * Test user can process import.
     */
    public function test_user_can_process_import(): void
    {
        $import = Import::factory()->pending()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/imports/{$import->id}/process");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Import processing started.',
                'data' => [
                    'id' => $import->id,
                    'status' => 'processing',
                ]
            ]);

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'status' => 'processing',
        ]);
    }

    /**
     * Test user can cancel import.
     */
    public function test_user_can_cancel_import(): void
    {
        $import = Import::factory()->processing()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/imports/{$import->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Import cancelled successfully.',
                'data' => [
                    'id' => $import->id,
                    'status' => 'failed',
                ]
            ]);

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'status' => 'failed',
        ]);
    }

    /**
     * Test user can retry failed import.
     */
    public function test_user_can_retry_failed_import(): void
    {
        $import = Import::factory()->failed()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'processed_rows' => 10,
            'failed_rows' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/imports/{$import->id}/retry");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Import queued for retry.',
                'data' => [
                    'id' => $import->id,
                    'status' => 'pending',
                    'processed_rows' => 0,
                    'successful_rows' => 0,
                    'failed_rows' => 0,
                ]
            ]);

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'status' => 'pending',
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
        ]);
    }

    /**
     * Test import validation fails with invalid data.
     */
    public function test_import_validation_fails_with_invalid_data(): void
    {
        $data = [
            'workspace_id' => 999999, // Non-existent workspace
            'type' => 'invalid_type',
            'entity' => 'invalid_entity',
            // Missing required file
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/imports', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id', 'type', 'entity', 'file']);
    }

    /**
     * Test unauthorized user cannot access other user's imports.
     */
    public function test_unauthorized_user_cannot_access_other_users_imports(): void
    {
        $otherUser = User::factory()->create();
        $import = Import::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/imports/{$import->id}");

        $response->assertStatus(403);
    }
}
