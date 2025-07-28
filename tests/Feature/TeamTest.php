<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $workspace;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->workspace->users()->attach($this->user->id, ['role' => 'owner']);
    }

    /**
     * Test team listing.
     */
    public function test_user_can_get_teams_list(): void
    {
        $teams = Team::factory()
            ->count(3)
            ->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.workspaces.teams.index', $this->workspace));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test team creation.
     */
    public function test_user_can_create_team(): void
    {
        $teamData = [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'color' => '#3B82F6',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('api.workspaces.teams.store', $this->workspace), $teamData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => $teamData['name'],
                'slug' => $teamData['slug'],
                'workspace_id' => $this->workspace->id,
            ]);

        $this->assertDatabaseHas('teams', [
            'name' => $teamData['name'],
            'slug' => $teamData['slug'],
            'workspace_id' => $this->workspace->id,
        ]);

        // Check if user is assigned as owner
        $team = Team::where('slug', $teamData['slug'])->first();
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    /**
     * Test viewing a single team.
     */
    public function test_user_can_view_team(): void
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.teams.show', $team));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $team->id,
                'name' => $team->name,
            ]);
    }

    /**
     * Test updating a team.
     */
    public function test_owner_can_update_team(): void
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);
        $team->users()->attach($this->user->id, ['role' => 'owner']);

        $updateData = [
            'name' => 'Updated Team Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('api.teams.update', $team), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Team Name',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Team Name',
        ]);
    }

    /**
     * Test team deletion.
     */
    public function test_owner_can_delete_team(): void
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);
        $team->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('api.teams.destroy', $team));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('teams', [
            'id' => $team->id,
        ]);
    }

    /**
     * Test assigning a user to a team.
     */
    public function test_owner_can_assign_user_to_team(): void
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);
        $team->users()->attach($this->user->id, ['role' => 'owner']);
        
        $newUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('api.teams.assign-user', $team), [
                'user_id' => $newUser->id,
                'role' => 'member',
            ]);

        $response->assertStatus(200);

        // The user should be added to both the workspace and team
        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $newUser->id,
        ]);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $newUser->id,
            'role' => 'member',
        ]);
    }
}
