<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pipeline Feature Tests
 */
class PipelineTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;
    protected Project $project;
    protected Status $status1;
    protected Status $status2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and workspace
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();

        // Attach user to workspace
        $this->workspace->users()->attach($this->user->id, ['role' => 'admin']);

        // Create a project
        $this->project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        // Create test statuses
        $this->status1 = Status::factory()->todo()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        $this->status2 = Status::factory()->done()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function test_user_can_list_pipelines()
    {
        // Create test pipelines
        $pipelines = Pipeline::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->getJson('/api/pipelines');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'is_default',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_create_pipeline()
    {
        $pipelineData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Pipeline',
            'description' => 'This is a test pipeline',
            'statuses' => [$this->status1->id, $this->status2->id],
        ];

        $response = $this->postJson('/api/pipelines', $pipelineData);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Pipeline created successfully.',
                    'data' => [
                        'name' => 'Test Pipeline',
                        'description' => 'This is a test pipeline',
                    ]
                ]);

        $this->assertDatabaseHas('pipelines', [
            'name' => 'Test Pipeline',
            'workspace_id' => $this->workspace->id,
        ]);

        // Check if statuses were attached
        $pipeline = Pipeline::where('name', 'Test Pipeline')->first();
        $this->assertCount(2, $pipeline->statuses);
    }

    /** @test */
    public function test_user_can_view_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $pipeline->statuses()->attach([
            $this->status1->id => ['order' => 0],
            $this->status2->id => ['order' => 1],
        ]);

        $response = $this->getJson("/api/pipelines/{$pipeline->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $pipeline->id,
                        'name' => $pipeline->name,
                        'slug' => $pipeline->slug,
                    ]
                ]);
                
        $response->assertJsonStructure([
            'data' => [
                'statuses' => [
                    '*' => [
                        'id',
                        'name',
                        'order',
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function test_user_can_update_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $updateData = [
            'name' => 'Updated Pipeline Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/pipelines/{$pipeline->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Pipeline updated successfully.',
                    'data' => [
                        'name' => 'Updated Pipeline Name',
                        'description' => 'Updated description',
                    ]
                ]);

        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline->id,
            'name' => 'Updated Pipeline Name',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function test_user_can_delete_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_default' => false,
        ]);

        // Create another pipeline to ensure there's not just one
        Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_default' => true,
        ]);

        $response = $this->deleteJson("/api/pipelines/{$pipeline->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Pipeline deleted successfully.'
                ]);

        $this->assertDatabaseMissing('pipelines', ['id' => $pipeline->id]);
    }

    /** @test */
    public function test_user_can_set_pipeline_as_default()
    {
        // Create two pipelines
        $pipeline1 = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_default' => false,
        ]);
        
        $pipeline2 = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
            'is_default' => true,
        ]);

        $response = $this->patchJson("/api/pipelines/{$pipeline1->id}/set-default");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Pipeline set as default successfully.',
                    'data' => [
                        'id' => $pipeline1->id,
                        'is_default' => true,
                    ]
                ]);

        // Check if pipeline1 is now default
        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline1->id,
            'is_default' => true,
        ]);

        // Check if pipeline2 is no longer default
        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline2->id,
            'is_default' => false,
        ]);
    }

    /** @test */
    public function test_user_can_add_status_to_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        // Create another status
        $status3 = Status::factory()->inProgress()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->postJson("/api/pipelines/{$pipeline->id}/add-status", [
            'status_id' => $status3->id,
            'order' => 2,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Status added to pipeline successfully.',
                ]);

        // Check if status was added to pipeline
        $this->assertDatabaseHas('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $status3->id,
            'order' => 2,
        ]);
    }

    /** @test */
    public function test_user_can_remove_status_from_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        // Attach status to pipeline
        $pipeline->statuses()->attach($this->status1->id, ['order' => 0]);

        $response = $this->deleteJson("/api/pipelines/{$pipeline->id}/remove-status", [
            'status_id' => $this->status1->id,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Status removed from pipeline successfully.',
                ]);

        // Check if status was removed from pipeline
        $this->assertDatabaseMissing('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $this->status1->id,
        ]);
    }

    /** @test */
    public function test_user_can_reorder_statuses_in_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        // Attach statuses to pipeline
        $pipeline->statuses()->attach([
            $this->status1->id => ['order' => 0],
            $this->status2->id => ['order' => 1],
        ]);

        $response = $this->patchJson("/api/pipelines/{$pipeline->id}/reorder-statuses", [
            'statuses_order' => [
                $this->status1->id => 1,
                $this->status2->id => 0,
            ],
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Pipeline statuses reordered successfully.',
                ]);

        // Check if statuses were reordered
        $this->assertDatabaseHas('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $this->status1->id,
            'order' => 1,
        ]);
        
        $this->assertDatabaseHas('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $this->status2->id,
            'order' => 0,
        ]);
    }

    /** @test */
    public function test_user_can_sync_statuses_to_pipeline()
    {
        $pipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        // Create another status
        $status3 = Status::factory()->inProgress()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->postJson("/api/pipelines/{$pipeline->id}/sync-statuses", [
            'statuses' => [$this->status1->id, $status3->id],
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Pipeline statuses updated successfully.',
                ]);

        // Check if statuses were synced
        $this->assertDatabaseHas('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $this->status1->id,
        ]);
        
        $this->assertDatabaseHas('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $status3->id,
        ]);
        
        // The status2 should not be attached
        $this->assertDatabaseMissing('pipeline_status', [
            'pipeline_id' => $pipeline->id,
            'status_id' => $this->status2->id,
        ]);
        
        // Check the total number of statuses in the pipeline
        $this->assertCount(2, $pipeline->fresh()->statuses);
    }

    /** @test */
    public function test_user_can_create_default_pipeline_for_workspace()
    {
        $response = $this->postJson("/api/pipelines/create-default", [
            'workspace_id' => $this->workspace->id,
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Default pipeline created successfully.',
                    'data' => [
                        'name' => 'Default Pipeline',
                        'is_default' => true,
                    ]
                ]);

        $this->assertDatabaseHas('pipelines', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Default Pipeline',
            'is_default' => 1,
        ]);
    }

    /** @test */
    public function test_project_can_have_pipeline()
    {
        $response = $this->postJson("/api/projects/{$this->project->id}/pipelines", [
            'name' => 'Project Pipeline',
            'description' => 'Pipeline for this project',
            'statuses' => [$this->status1->id, $this->status2->id],
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Project pipeline created successfully.',
                    'data' => [
                        'name' => 'Project Pipeline',
                        'project_id' => $this->project->id,
                    ]
                ]);

        $this->assertDatabaseHas('pipelines', [
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'name' => 'Project Pipeline',
        ]);
    }

    /** @test */
    public function test_user_can_get_default_pipeline_for_project()
    {
        // First create a project-specific pipeline
        $projectPipeline = Pipeline::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'is_default' => true,
        ]);

        $response = $this->getJson("/api/projects/{$this->project->id}/default-pipeline");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $projectPipeline->id,
                        'name' => $projectPipeline->name,
                        'project_id' => $this->project->id,
                    ]
                ]);
    }
}
