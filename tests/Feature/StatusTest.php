<?php
// tests/Feature/StatusTest.php

namespace Tests\Feature;

use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Status Feature Tests
 */
class StatusTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and workspace
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();

        // Attach user to workspace
        $this->workspace->users()->attach($this->user->id, ['role' => 'admin']);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function test_user_can_list_statuses()
    {
        // Create test statuses
        $statuses = Status::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson('/api/statuses');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'color',
                            'type',
                            'order',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_create_status()
    {
        $statusData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Status',
            'color' => '#10B981',
            'type' => 'todo',
            'order' => 1,
        ];

        $response = $this->postJson('/api/statuses', $statusData);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Status created successfully.',
                    'data' => [
                        'name' => 'Test Status',
                        'type' => 'todo',
                    ]
                ]);

        $this->assertDatabaseHas('statuses', [
            'name' => 'Test Status',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    /** @test */
    public function test_user_can_view_status()
    {
        $status = Status::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson("/api/statuses/{$status->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $status->id,
                        'name' => $status->name,
                        'slug' => $status->slug,
                    ]
                ]);
    }

    /** @test */
    public function test_user_can_update_status()
    {
        $status = Status::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $updateData = [
            'name' => 'Updated Status Name',
            'color' => '#EF4444',
            'type' => 'done',
        ];

        $response = $this->putJson("/api/statuses/{$status->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Status updated successfully.',
                    'data' => [
                        'name' => 'Updated Status Name',
                        'color' => '#EF4444',
                        'type' => 'done',
                    ]
                ]);

        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'name' => 'Updated Status Name',
            'color' => '#EF4444',
            'type' => 'done',
        ]);
    }

    /** @test */
    public function test_user_can_delete_status()
    {
        $status = Status::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_default' => false,
        ]);

        $response = $this->deleteJson("/api/statuses/{$status->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Status deleted successfully.'
                ]);

        $this->assertDatabaseMissing('statuses', ['id' => $status->id]);
    }

    /** @test */
    public function test_user_cannot_delete_default_status()
    {
        $status = Status::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_default' => true,
        ]);

        $response = $this->deleteJson("/api/statuses/{$status->id}");

        $response->assertStatus(403); // Forbidden

        $this->assertDatabaseHas('statuses', ['id' => $status->id]);
    }

    /** @test */
    public function test_user_can_list_statuses_by_workspace()
    {
        // Create statuses for this workspace
        Status::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Create statuses for another workspace
        $otherWorkspace = Workspace::factory()->create();
        Status::factory()->count(2)->create([
            'workspace_id' => $otherWorkspace->id,
        ]);

        $response = $this->getJson("/api/workspaces/{$this->workspace->id}/statuses");

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_list_statuses_by_type()
    {
        // Create statuses with different types
        Status::factory()->ofType('todo')->count(2)->create([
            'workspace_id' => $this->workspace->id,
        ]);
        Status::factory()->ofType('in_progress')->count(1)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson("/api/statuses/type/todo?workspace_id={$this->workspace->id}");

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_user_can_create_default_statuses()
    {
        $response = $this->postJson("/api/statuses/create-default", [
            'workspace_id' => $this->workspace->id,
        ]);

        $response->assertStatus(201)
                ->assertJsonCount(5, 'data');

        // Check if all default status types were created
        $this->assertDatabaseHas('statuses', [
            'workspace_id' => $this->workspace->id,
            'type' => 'backlog',
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('statuses', [
            'workspace_id' => $this->workspace->id,
            'type' => 'todo',
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('statuses', [
            'workspace_id' => $this->workspace->id,
            'type' => 'in_progress',
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('statuses', [
            'workspace_id' => $this->workspace->id,
            'type' => 'done',
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('statuses', [
            'workspace_id' => $this->workspace->id,
            'type' => 'cancelled',
            'is_default' => 1,
        ]);
    }
}
