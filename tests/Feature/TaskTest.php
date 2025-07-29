<?php

namespace Tests\Feature;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Workspace $workspace;
    private Project $project;
    private Status $status;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create workspace
        $this->workspace = Workspace::factory()->create();
        
        // Attach user to workspace (assuming you have a workspace_user pivot table)
        // $this->workspace->users()->attach($this->user->id);
        
        // Create project
        $this->project = Project::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        
        // Create status
        $this->status = Status::factory()->create();
    }

    /**
     * Test user can list tasks.
     */
    public function test_user_can_list_tasks(): void
    {
        // Create some tasks
        $tasks = Task::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?workspace_id=' . $this->workspace->id);

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'priority',
                        'type',
                        'due_date',
                        'status_id',
                        'workspace_id',
                        'project_id',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test user can create a task.
     */
    public function test_user_can_create_task(): void
    {
        $taskData = [
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'status_id' => $this->status->id,
            'reporter_id' => $this->user->id,
            'assignee_id' => $this->user->id,
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'priority' => 'high',
            'type' => 'task',
            'due_date' => now()->addDays(7)->toDateTimeString(),
        ];

        // Make the request
        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', $taskData);

        // Assert the response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'priority',
                    'type',
                    'due_date',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Task',
                    'description' => 'This is a test task',
                    'priority' => 'high',
                    'type' => 'task',
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
        ]);
    }

    /**
     * Test user can view a task.
     */
    public function test_user_can_view_task(): void
    {
        // Create a task
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
            'title' => 'View Task Test',
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->getJson("/api/tasks/{$task->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'View Task Test',
                ]
            ]);
    }

    /**
     * Test user can update a task.
     */
    public function test_user_can_update_task(): void
    {
        // Create a task
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated task description',
            'priority' => 'urgent',
        ];

        // Make the request
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", $updateData);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Updated Task Title',
                    'description' => 'Updated task description',
                    'priority' => 'urgent',
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'description' => 'Updated task description',
            'priority' => 'urgent',
        ]);
    }

    /**
     * Test user can delete a task.
     */
    public function test_user_can_delete_task(): void
    {
        // Create a task
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}");

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Task deleted successfully'
            ]);

        // Check the database
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test user can change task status.
     */
    public function test_user_can_change_task_status(): void
    {
        // Create a task
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        // Create a new status
        $newStatus = Status::factory()->create();

        // Make the request
        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status_id' => $newStatus->id,
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'status_id' => $newStatus->id,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status_id' => $newStatus->id,
        ]);
    }

    /**
     * Test user can assign a task to another user.
     */
    public function test_user_can_assign_task(): void
    {
        // Create a task
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
            'assignee_id' => null,
        ]);

        // Create another user
        $assignee = User::factory()->create();

        // Make the request
        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/assign", [
                'assignee_id' => $assignee->id,
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'assignee_id' => $assignee->id,
                ]
            ]);

        // Check the database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assignee_id' => $assignee->id,
        ]);
    }

    /**
     * Test user can add dependencies to a task.
     */
    public function test_user_can_add_dependency(): void
    {
        // Create two tasks
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        $dependencyTask = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/dependencies", [
                'dependency_ids' => [$dependencyTask->id],
                'dependency_types' => ['blocks'],
            ]);

        // Assert the response
        $response->assertStatus(200);

        // Check the database
        $this->assertDatabaseHas('task_dependencies', [
            'task_id' => $task->id,
            'depends_on_id' => $dependencyTask->id,
            'type' => 'blocks',
        ]);
    }

    /**
     * Test user can associate milestones with a task.
     */
    public function test_user_can_associate_milestones(): void
    {
        // Create a task
        $task = Task::factory()->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status_id' => $this->status->id,
        ]);

        // Create milestones
        $milestone1 = Milestone::factory()->create(['project_id' => $this->project->id]);
        $milestone2 = Milestone::factory()->create(['project_id' => $this->project->id]);

        // Make the request
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}/milestones", [
                'milestone_ids' => [$milestone1->id, $milestone2->id],
            ]);

        // Assert the response
        $response->assertStatus(200);

        // Check the database
        $this->assertDatabaseHas('task_milestone', [
            'task_id' => $task->id,
            'milestone_id' => $milestone1->id,
        ]);
        
        $this->assertDatabaseHas('task_milestone', [
            'task_id' => $task->id,
            'milestone_id' => $milestone2->id,
        ]);
    }
}
