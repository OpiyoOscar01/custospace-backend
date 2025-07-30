<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Mention;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MentionTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Test user can list their mentions.
     */
    public function test_user_can_list_their_mentions(): void
    {
        // Create mentions for the user
        Mention::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);
        
        // Create mentions for another user
        Mention::factory()->count(2)->create();
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/mentions');
        
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
    
    /**
     * Test user can filter mentions by read status.
     */
    public function test_user_can_filter_mentions_by_read_status(): void
    {
        // Create read mentions
        Mention::factory()->count(2)->read()->create([
            'user_id' => $this->user->id,
        ]);
        
        // Create unread mentions
        Mention::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/mentions?is_read=0');
        
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
    
    /**
     * Test user can view a specific mention.
     */
    public function test_user_can_view_a_specific_mention(): void
    {
        $mention = Mention::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/mentions/{$mention->id}");
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $mention->id,
                ]);
                
        // Check that viewing marks it as read
        $this->assertDatabaseHas('mentions', [
            'id' => $mention->id,
            'is_read' => true,
        ]);
    }
    
    /**
     * Test user cannot view someone else's mention.
     */
    public function test_user_cannot_view_someone_elses_mention(): void
    {
        $otherUser = User::factory()->create();
        $mention = Mention::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/mentions/{$mention->id}");
        
        $response->assertStatus(403);
    }
    
    /**
     * Test user can mark a mention as read.
     */
    public function test_user_can_mark_a_mention_as_read(): void
    {
        $mention = Mention::factory()->unread()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->patchJson("/api/mentions/{$mention->id}/read");
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'is_read' => true,
                ]);
                
        $this->assertDatabaseHas('mentions', [
            'id' => $mention->id,
            'is_read' => true,
        ]);
    }
    
    /**
     * Test user can mark all their mentions as read.
     */
    public function test_user_can_mark_all_their_mentions_as_read(): void
    {
        // Create unread mentions
        Mention::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/mentions/mark-all-read');
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'count' => 3,
                ]);
                
        $this->assertDatabaseMissing('mentions', [
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
    }
    
    /**
     * Test user can get their unread mentions count.
     */
    public function test_user_can_get_their_unread_mentions_count(): void
    {
        // Create unread mentions
        Mention::factory()->count(4)->unread()->create([
            'user_id' => $this->user->id,
        ]);
        
        // Create read mentions
        Mention::factory()->count(2)->read()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/mentions/unread-count');
        
        $response->assertStatus(200)
                ->assertJson([
                    'count' => 4,
                ]);
    }
    
    /**
     * Test user can delete their mention.
     */
    public function test_user_can_delete_their_mention(): void
    {
        $mention = Mention::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/mentions/{$mention->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('mentions', [
            'id' => $mention->id,
        ]);
    }
    
    /**
     * Test user cannot delete someone else's mention.
     */
    public function test_user_cannot_delete_someone_elses_mention(): void
    {
        $otherUser = User::factory()->create();
        $mention = Mention::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/mentions/{$mention->id}");
        
        $response->assertStatus(403);
        
        $this->assertDatabaseHas('mentions', [
            'id' => $mention->id,
        ]);
    }
}
