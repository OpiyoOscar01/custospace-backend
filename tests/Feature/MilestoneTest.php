<?php

namespace Tests\Feature;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Status;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create workspace
        $workspace = Workspace::factory()->create();
        
        // Create project
        $this->project = Project::factory()->create([
            'workspace_id' => $workspace->id
        ]);
        
        // Create status
        $status = Status::factory()->create();
        
        // Create task
        $this->task = Task::factory()->create([
            'workspace_id' => $workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $status->id,
        ]);
    }

    /**
     * Test user can list milestones.
     */
    public function test_user_can_list_milestones(): void
    {
        // Create some milestones
        $milestones = Milestone::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->getJson('/api/milestones?project_id=' . $this->project->id);

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'project_id',
                        'name',
                        'description',
                        'due_date',
                        'is_completed',
                        'order',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test user can create a milestone.
     */
    public function test_user_can_create_milestone(): void
    {
        $milestoneData = [
            'project_id' => $this->project->id,
            'name' => 'Test Milestone',
            'description' => 'This is a test milestone',
            'due_date' => now()->addMonth()->toDateString(),
            'is_completed' => false,
            'task_ids' => [$this->task->id],
        ];

        // Make the request
        $response = $this->actingAs($this->user)
            ->postJson('/api/milestones', $milestoneData);

        // Assert the response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'project_id',
                    'name',
                    'description',
                    'due_date',
                    'is_completed',
                    'order',
                ]
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test Milestone',
                    'description' => 'This is a test milestone',
                    'is_completed' => false,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('milestones', [
            'name' => 'Test Milestone',
            'project_id' => $this->project->id,
        ]);
        
        // Check that the task is associated with the milestone
        $milestone = Milestone::where('name', 'Test Milestone')->first();
        $this->assertDatabaseHas('task_milestone', [
            'task_id' => $this->task->id,
            'milestone_id' => $milestone->id,
        ]);
    }

    /**
     * Test user can view a milestone.
     */
    public function test_user_can_view_milestone(): void
    {
        // Create a milestone
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'View Milestone Test',
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->getJson("/api/milestones/{$milestone->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $milestone->id,
                    'name' => 'View Milestone Test',
                ]
            ]);
    }

    /**
     * Test user can update a milestone.
     */
    public function test_user_can_update_milestone(): void
    {
        // Create a milestone
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $updateData = [
            'name' => 'Updated Milestone Name',
            'description' => 'Updated milestone description',
            'due_date' => now()->addMonths(2)->toDateString(),
        ];

        // Make the request
        $response = $this->actingAs($this->user)
            ->putJson("/api/milestones/{$milestone->id}", $updateData);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $milestone->id,
                    'name' => 'Updated Milestone Name',
                    'description' => 'Updated milestone description',
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('milestones', [
            'id' => $milestone->id,
            'name' => 'Updated Milestone Name',
            'description' => 'Updated milestone description',
        ]);
    }

    /**
     * Test user can delete a milestone.
     */
    public function test_user_can_delete_milestone(): void
    {
        // Create a milestone
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/milestones/{$milestone->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Milestone deleted successfully'
            ]);

        // Check the database
        $this->assertDatabaseMissing('milestones', [
            'id' => $milestone->id,
        ]);
    }

    /**
     * Test user can toggle completion status of a milestone.
     */
    public function test_user_can_toggle_milestone_completion(): void
    {
        // Create a milestone
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
            'is_completed' => false,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->patchJson("/api/milestones/{$milestone->id}/toggle-completion", [
                'is_completed' => true,
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $milestone->id,
                    'is_completed' => true,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('milestones', [
            'id' => $milestone->id,
            'is_completed' => true,
        ]);
    }

    /**
     * Test user can associate tasks with a milestone.
     */
    public function test_user_can_sync_tasks_with_milestone(): void
    {
        // Create a milestone
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        // Create additional tasks
        $task2 = Task::factory()->create([
            'workspace_id' => $this->project->workspace_id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);
        
        $task3 = Task::factory()->create([
            'workspace_id' => $this->project->workspace_id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->putJson("/api/milestones/{$milestone->id}/tasks", [
                'task_ids' => [$this->task->id, $task2->id],
            ]);

        // Assert the response
        $response->assertStatus(200);

        // Check the database
        $this->assertDatabaseHas('task_milestone', [
            'task_id' => $this->task->id,
            'milestone_id' => $milestone->id,
        ]);
        
        $this->assertDatabaseHas('task_milestone', [
            'task_id' => $task2->id,
            'milestone_id' => $milestone->id,
        ]);
        
        // Make sure task3 is not associated
        $this->assertDatabaseMissing('task_milestone', [
            'task_id' => $task3->id,
            'milestone_id' => $milestone->id,
        ]);
    }

    /**
     * Test user can reorder milestones.
     */
    public function test_user_can_reorder_milestones(): void
    {
        // Create milestones
        $milestone1 = Milestone::factory()->create(['project_id' => $this->project->id, 'order' => 0]);
        $milestone2 = Milestone::factory()->create(['project_id' => $this->project->id, 'order' => 1]);
        $milestone3 = Milestone::factory()->create(['project_id' => $this->project->id, 'order' => 2]);

        // Make the request to reorder
        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/milestones/reorder", [
                'milestone_ids' => [$milestone3->id, $milestone1->id, $milestone2->id],
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Milestones reordered successfully'
            ]);

        // Check the database for new order
        $this->assertDatabaseHas('milestones', [
            'id' => $milestone3->id,
            'order' => 0,
        ]);
        
        $this->assertDatabaseHas('milestones', [
            'id' => $milestone1->id,
            'order' => 1,
        ]);
        
        $this->assertDatabaseHas('milestones', [
            'id' => $milestone2->id,
            'order' => 2,
        ]);
    }
}
