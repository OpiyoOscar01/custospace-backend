<?php
namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Project Feature Tests
 * 
 * Tests all project API endpoints and functionality.
 */
class ProjectTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and workspace
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->team = Team::factory()->create(['workspace_id' => $this->workspace->id]);

        // Attach user to workspace
        $this->workspace->users()->attach($this->user->id, ['role' => 'admin']);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function test_user_can_list_projects()
    {
        // Create test projects
        $projects = Project::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'status',
                            'priority',
                            'progress',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_create_project()
    {
        $projectData = [
            'workspace_id' => $this->workspace->id,
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'color' => '#10B981',
            'status' => 'active',
            'priority' => 'high',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'budget' => 50000.00,
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Project created successfully.',
                    'data' => [
                        'name' => 'Test Project',
                        'status' => 'active',
                        'priority' => 'high',
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        // Check that owner is automatically assigned to project
        $project = Project::where('name', 'Test Project')->first();
        $this->assertTrue($project->users()->where('user_id', $this->user->id)->exists());
    }

    /** @test */
    public function test_user_can_view_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'slug' => $project->slug,
                    ]
                ]);
    }

    /** @test */
    public function test_user_can_update_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Project Name',
            'description' => 'Updated description',
            'status' => 'completed',
            'progress' => 100,
        ];

        $response = $this->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Project updated successfully.',
                    'data' => [
                        'name' => 'Updated Project Name',
                        'status' => 'completed',
                        'progress' => 100,
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function test_user_can_delete_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Project deleted successfully.'
                ]);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    /** @test */
    public function test_user_can_activate_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->patchJson("/api/projects/{$project->id}/activate");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Project activated successfully.',
                    'data' => [
                        'status' => 'active',
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_user_can_deactivate_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->patchJson("/api/projects/{$project->id}/deactivate");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Project deactivated successfully.',
                    'data' => [
                        'status' => 'on_hold',
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'on_hold',
        ]);
    }

    /** @test */
    public function test_user_can_complete_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->patchJson("/api/projects/{$project->id}/complete");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Project completed successfully.',
                    'data' => [
                        'status' => 'completed',
                        'progress' => 100,
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'completed',
            'progress' => 100,
        ]);
    }

    /** @test */
    public function test_user_can_assign_user_to_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $newUser = User::factory()->create();

        $response = $this->postJson("/api/projects/{$project->id}/assign-user", [
            'user_id' => $newUser->id,
            'role' => 'contributor',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'User assigned to project successfully.',
                ]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $newUser->id,
            'role' => 'contributor',
        ]);
    }

    /** @test */
    public function test_user_can_update_progress()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'progress' => 50,
        ]);

        $response = $this->patchJson("/api/projects/{$project->id}/progress", [
            'progress' => 75,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Project progress updated successfully.',
                    'data' => [
                        'progress' => 75,
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'progress' => 75,
        ]);
    }

    /** @test */
    public function test_unauthorized_user_cannot_view_project()
    {
        $unauthorizedUser = User::factory()->create();
        Sanctum::actingAs($unauthorizedUser);

        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function test_project_creation_validation()
    {
        $response = $this->postJson('/api/projects', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['workspace_id', 'owner_id', 'name']);
    }

    /** @test */
    public function test_project_slug_uniqueness_within_workspace()
    {
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'slug' => 'test-project',
        ]);

        $response = $this->postJson('/api/projects', [
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function test_project_statistics()
    {
        // Create projects with different statuses
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => 'active',
        ]);
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => 'completed',
        ]);
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => 'on_hold',
        ]);

        $response = $this->getJson('/api/projects-statistics?workspace_id=' . $this->workspace->id);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total',
                        'active',
                        'completed',
                        'on_hold',
                        'draft',
                        'cancelled',
                        'high_priority',
                        'urgent_priority',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'total' => 3,
                        'active' => 1,
                        'completed' => 1,
                        'on_hold' => 1,
                    ]
                ]);
    }

    /** @test */
    public function test_project_filtering()
    {
        // Create projects with different attributes
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => 'active',
            'priority' => 'high',
        ]);
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => 'completed',
            'priority' => 'low',
        ]);

        // Test status filtering
        $response = $this->getJson('/api/projects?status=active');
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data');

        // Test priority filtering
        $response = $this->getJson('/api/projects?priority=high');
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_progress_update_automatically_completes_project()
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'status' => 'active',
            'progress' => 90,
        ]);

        $response = $this->patchJson("/api/projects/{$project->id}/progress", [
            'progress' => 100,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'progress' => 100,
                        'status' => 'completed',
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'progress' => 100,
            'status' => 'completed',
        ]);
    }
}
