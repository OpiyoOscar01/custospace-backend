<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workspace $workspace;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Attach user to workspace (this depends on your workspace-user relationship)
        $this->workspace->users()->attach($this->user->id);
    }

    /** @test */
    public function test_user_can_list_tags()
    {
        $tags = Tag::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tags?workspace_id={$this->workspace->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'color', 'description']
                ],
                'links',
                'meta'
            ]);
    }

    /** @test */
    public function test_user_can_create_tag()
    {
        $tagData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'New Tag',
            'color' => '#FF5733',
            'description' => 'A test tag',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/tags', $tagData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'New Tag',
                'slug' => 'new-tag',
                'color' => '#FF5733',
                'description' => 'A test tag',
            ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'New Tag',
            'slug' => 'new-tag',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    /** @test */
    public function test_user_can_view_tag()
    {
        $tag = Tag::factory()->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]);
    }

    /** @test */
    public function test_user_can_update_tag()
    {
        $tag = Tag::factory()->create(['workspace_id' => $this->workspace->id]);

        $updateData = [
            'name' => 'Updated Tag',
            'color' => '#33FF57',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/tags/{$tag->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Tag',
                'slug' => 'updated-tag',
                'color' => '#33FF57',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Tag',
            'slug' => 'updated-tag',
        ]);
    }

    /** @test */
    public function test_user_can_delete_tag()
    {
        $tag = Tag::factory()->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function test_user_can_assign_tag_to_task()
    {
        $tag = Tag::factory()->create(['workspace_id' => $this->workspace->id]);
        $task = Task::factory()->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/tags/{$tag->id}/assign-to-task", [
                'task_id' => $task->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Tag assigned successfully']);

        $this->assertDatabaseHas('task_tag', [
            'task_id' => $task->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function test_user_can_remove_tag_from_task()
    {
        $tag = Tag::factory()->create(['workspace_id' => $this->workspace->id]);
        $task = Task::factory()->create(['workspace_id' => $this->workspace->id]);

        // First assign the tag
        $tag->tasks()->attach($task->id);
        $this->assertDatabaseHas('task_tag', ['task_id' => $task->id, 'tag_id' => $tag->id]);

        // Then remove it
        $response = $this->actingAs($this->user)
            ->postJson("/api/tags/{$tag->id}/remove-from-task", [
                'task_id' => $task->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Tag removed successfully']);

        $this->assertDatabaseMissing('task_tag', ['task_id' => $task->id, 'tag_id' => $tag->id]);
    }

    /** @test */
    public function test_user_can_get_tags_by_task()
    {
        $tag1 = Tag::factory()->create(['workspace_id' => $this->workspace->id]);
        $tag2 = Tag::factory()->create(['workspace_id' => $this->workspace->id]);
        $task = Task::factory()->create(['workspace_id' => $this->workspace->id]);
        
        $task->tags()->attach([$tag1->id, $tag2->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tasks/tags?task_id={$task->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $tag1->id])
            ->assertJsonFragment(['id' => $tag2->id]);
    }
}
