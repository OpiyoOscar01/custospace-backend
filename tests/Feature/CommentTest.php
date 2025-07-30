<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Test user can list comments.
     */
    public function test_user_can_list_comments(): void
    {
        // Create some comments
        Comment::factory()->count(5)->create();
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/comments');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id', 'user_id', 'content', 'created_at'
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }
    
    /**
     * Test user can filter comments by commentable.
     */
    public function test_user_can_filter_comments_by_commentable(): void
    {
        $commentableType = 'App\Models\Task';
        $commentableId = 1;
        
        // Create some comments for a specific commentable
        Comment::factory()->count(3)->create([
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
        ]);
        
        // Create other random comments
        Comment::factory()->count(2)->create();
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/comments/by-commentable?' . http_build_query([
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
        ]));
        
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
    
    /**
     * Test user can create comment.
     */
    public function test_user_can_create_comment(): void
    {
        Sanctum::actingAs($this->user);
        
        $commentData = [
            'commentable_type' => 'App\Models\Task',
            'commentable_id' => 1,
            'content' => $this->faker->paragraph(),
            'is_internal' => false,
        ];
        
        $response = $this->postJson('/api/comments', $commentData);
        
        $response->assertStatus(201)
                ->assertJsonFragment([
                    'user_id' => $this->user->id,
                    'content' => $commentData['content'],
                    'commentable_type' => $commentData['commentable_type'],
                    'commentable_id' => $commentData['commentable_id'],
                ]);
    }
    
    /**
     * Test user can view comment.
     */
    public function test_user_can_view_comment(): void
    {
        $comment = Comment::factory()->create();
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/comments/{$comment->id}");
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $comment->id,
                    'content' => $comment->content,
                ]);
    }
    
    /**
     * Test user can update their own comment.
     */
    public function test_user_can_update_their_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $updatedContent = 'Updated comment content';
        
        $response = $this->putJson("/api/comments/{$comment->id}", [
            'content' => $updatedContent,
        ]);
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $comment->id,
                    'content' => $updatedContent,
                    'is_edited' => true,
                ]);
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => $updatedContent,
            'is_edited' => true,
        ]);
    }
    
    /**
     * Test user cannot update someone else's comment.
     */
    public function test_user_cannot_update_someone_elses_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'This should not update',
        ]);
        
        $response->assertStatus(403);
    }
    
    /**
     * Test user can delete their own comment.
     */
    public function test_user_can_delete_their_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/comments/{$comment->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
    
    /**
     * Test user cannot delete someone else's comment.
     */
    public function test_user_cannot_delete_someone_elses_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/comments/{$comment->id}");
        
        $response->assertStatus(403);
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }
    
    /**
     * Test toggle internal status on comment.
     */
    public function test_toggle_internal_status(): void
    {
        // Create a user with permissions
        $adminUser = User::factory()->create();
        
        // Mock the hasPermissionTo method to return true
        $this->mock(\App\Models\User::class, function ($mock) {
            $mock->shouldReceive('hasPermissionTo')->andReturn(true);
        });
        
        $comment = Comment::factory()->create([
            'is_internal' => false,
        ]);
        
        Sanctum::actingAs($adminUser);
        
        $response = $this->patchJson("/api/comments/{$comment->id}/toggle-internal");
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'is_internal' => true,
                ]);
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_internal' => true,
        ]);
    }
}