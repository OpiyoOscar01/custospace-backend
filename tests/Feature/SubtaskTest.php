<?php

namespace Tests\Feature;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\Status;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubtaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create workspace and project
        $workspace = Workspace::factory()->create();
        $project = Project::factory()->create([
            'workspace_id' => $workspace->id
        ]);
        $status = Status::factory()->create();

        // Create task
        $this->task = Task::factory()->create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $status->id,
        ]);
    }

    /**
     * Test user can list subtasks for a task.
     */
    public function test_user_can_list_subtasks(): void
    {
        // Create some subtasks
        $subtasks = Subtask::factory()->count(3)->create([
            'task_id' => $this->task->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->getJson("/api/tasks/{$this->task->id}/subtasks");

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'task_id',
                        'title',
                        'description',
                        'is_completed',
                        'order',
                    ]
                ]
            ]);
    }

    /**
     * Test user can create a subtask.
     */
    public function test_user_can_create_subtask(): void
    {
        $subtaskData = [
            'task_id' => $this->task->id,
            'title' => 'Test Subtask',
            'description' => 'This is a test subtask',
            'is_completed' => false,
        ];

        // Make the request
        $response = $this->actingAs($this->user)
            ->postJson('/api/subtasks', $subtaskData);

        // Assert the response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'title',
                    'description',
                    'is_completed',
                    'order',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Subtask',
                    'description' => 'This is a test subtask',
                    'is_completed' => false,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('subtasks', [
            'title' => 'Test Subtask',
            'task_id' => $this->task->id,
        ]);
    }

    /**
     * Test user can view a subtask.
     */
    public function test_user_can_view_subtask(): void
    {
        // Create a subtask
        $subtask = Subtask::factory()->create([
            'task_id' => $this->task->id,
            'title' => 'View Subtask Test',
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->getJson("/api/subtasks/{$subtask->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $subtask->id,
                    'title' => 'View Subtask Test',
                ]
            ]);
    }

    /**
     * Test user can update a subtask.
     */
    public function test_user_can_update_subtask(): void
    {
        // Create a subtask
        $subtask = Subtask::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $updateData = [
            'title' => 'Updated Subtask Title',
            'description' => 'Updated subtask description',
            'is_completed' => true,
        ];

        // Make the request
        $response = $this->actingAs($this->user)
            ->putJson("/api/subtasks/{$subtask->id}", $updateData);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $subtask->id,
                    'title' => 'Updated Subtask Title',
                    'description' => 'Updated subtask description',
                    'is_completed' => true,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('subtasks', [
            'id' => $subtask->id,
            'title' => 'Updated Subtask Title',
            'description' => 'Updated subtask description',
            'is_completed' => true,
        ]);
    }

    /**
     * Test user can delete a subtask.
     */
    public function test_user_can_delete_subtask(): void
    {
        // Create a subtask
        $subtask = Subtask::factory()->create([
            'task_id' => $this->task->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/subtasks/{$subtask->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subtask deleted successfully'
            ]);

        // Check the database
        $this->assertDatabaseMissing('subtasks', [
            'id' => $subtask->id,
        ]);
    }

    /**
     * Test user can toggle completion status of a subtask.
     */
    public function test_user_can_toggle_subtask_completion(): void
    {
        // Create a subtask
        $subtask = Subtask::factory()->create([
            'task_id' => $this->task->id,
            'is_completed' => false,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->patchJson("/api/subtasks/{$subtask->id}/toggle-completion", [
                'is_completed' => true,
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $subtask->id,
                    'is_completed' => true,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('subtasks', [
            'id' => $subtask->id,
            'is_completed' => true,
        ]);
    }

    /**
     * Test user can reorder subtasks.
     */
    public function test_user_can_reorder_subtasks(): void
    {
        // Create subtasks
        $subtask1 = Subtask::factory()->create(['task_id' => $this->task->id, 'order' => 0]);
        $subtask2 = Subtask::factory()->create(['task_id' => $this->task->id, 'order' => 1]);
        $subtask3 = Subtask::factory()->create(['task_id' => $this->task->id, 'order' => 2]);

        // Make the request to reorder
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/subtasks/reorder", [
                'subtask_ids' => [$subtask3->id, $subtask1->id, $subtask2->id],
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subtasks reordered successfully'
            ]);

        // Check the database for new order
        $this->assertDatabaseHas('subtasks', [
            'id' => $subtask3->id,
            'order' => 0,
        ]);
        
        $this->assertDatabaseHas('subtasks', [
            'id' => $subtask1->id,
            'order' => 1,
        ]);
        
        $this->assertDatabaseHas('subtasks', [
            'id' => $subtask2->id,
            'order' => 2,
        ]);
    }
}
