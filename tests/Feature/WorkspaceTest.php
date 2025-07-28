<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test workspace listing.
     */
    public function test_user_can_get_workspaces_list(): void
    {
        $workspaces = Workspace::factory()
            ->count(3)
            ->create();

        // Attach user to workspaces as owner
        foreach ($workspaces as $workspace) {
            $workspace->users()->attach($this->user->id, ['role' => 'owner']);
        }

        $response = $this->actingAs($this->user)
            ->getJson(route('api.workspaces.index'));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test workspace creation.
     */
    public function test_user_can_create_workspace(): void
    {
        $workspaceData = [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('api.workspaces.store'), $workspaceData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => $workspaceData['name'],
                'slug' => $workspaceData['slug'],
            ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => $workspaceData['name'],
            'slug' => $workspaceData['slug'],
        ]);

        // Check if user is assigned as owner
        $workspace = Workspace::where('slug', $workspaceData['slug'])->first();
        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    /**
     * Test viewing a single workspace.
     */
    public function test_user_can_view_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.workspaces.show', $workspace));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $workspace->id,
                'name' => $workspace->name,
            ]);
    }

    /**
     * Test updating a workspace.
     */
    public function test_owner_can_update_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($this->user->id, ['role' => 'owner']);

        $updateData = [
            'name' => 'Updated Workspace Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('api.workspaces.update', $workspace), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Workspace Name',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Workspace Name',
        ]);
    }

    /**
     * Test workspace deletion.
     */
    public function test_owner_can_delete_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('api.workspaces.destroy', $workspace));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    /**
     * Test activating a workspace.
     */
    public function test_owner_can_activate_workspace(): void
    {
        $workspace = Workspace::factory()->create(['is_active' => false]);
        $workspace->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('api.workspaces.activate', $workspace));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test deactivating a workspace.
     */
    public function test_owner_can_deactivate_workspace(): void
    {
        $workspace = Workspace::factory()->create(['is_active' => true]);
        $workspace->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('api.workspaces.deactivate', $workspace));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test assigning a user to a workspace.
     */
    public function test_owner_can_assign_user_to_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($this->user->id, ['role' => 'owner']);
        
        $newUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('api.workspaces.assign-user', $workspace), [
                'user_id' => $newUser->id,
                'role' => 'member',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $newUser->id,
            'role' => 'member',
        ]);
    }
}
