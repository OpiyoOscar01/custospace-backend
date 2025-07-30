<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    private User $user;
    private Workspace $workspace;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Add the user to the workspace
        $this->workspace->users()->attach($this->user->id);
        
        // Create a conversation and add the user
        $this->conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        $this->conversation->users()->attach($this->user->id, [
            'role' => 'member',
        ]);
    }

    /**
     * Test user can list messages in a conversation.
     */
    public function test_user_can_list_messages(): void
    {
        Message::factory()->count(5)->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/conversations/{$this->conversation->id}/messages");
        
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id', 'conversation_id', 'user_id', 'content', 'type', 'created_at'
                        ]
                    ]
                ]);
    }
    
    /**
     * Test user cannot list messages in a conversation they are not part of.
     */
    public function test_user_cannot_list_messages_in_conversation_they_are_not_part_of(): void
    {
        $otherConversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/conversations/{$otherConversation->id}/messages");
        
        $response->assertStatus(403);
    }
    
    /**
     * Test user can create a message.
     */
    public function test_user_can_create_message(): void
    {
        Sanctum::actingAs($this->user);
        
        $messageData = [
            'conversation_id' => $this->conversation->id,
            'content' => 'Test message content',
        ];
        
        $response = $this->postJson('/api/messages', $messageData);
        
        $response->assertStatus(201)
                ->assertJsonFragment([
                    'conversation_id' => $this->conversation->id,
                    'content' => $messageData['content'],
                    'user_id' => $this->user->id,
                ]);
        
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'content' => $messageData['content'],
            'user_id' => $this->user->id,
        ]);
    }
    
    /**
     * Test user cannot create a message in a conversation they are not part of.
     */
    public function test_user_cannot_create_message_in_conversation_they_are_not_part_of(): void
    {
        $otherConversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $messageData = [
            'conversation_id' => $otherConversation->id,
            'content' => 'This should not be allowed',
        ];
        
        $response = $this->postJson('/api/messages', $messageData);
        
        $response->assertStatus(403);
    }
    
    /**
     * Test user can update their own message.
     */
    public function test_user_can_update_their_own_message(): void
    {
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);
        
        Sanctum::actingAs($this->user);
        
        $updatedContent = 'Updated content';
        
        $response = $this->putJson("/api/messages/{$message->id}", [
            'content' => $updatedContent,
        ]);
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $message->id,
                    'content' => $updatedContent,
                    'is_edited' => true,
                ]);
        
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'content' => $updatedContent,
            'is_edited' => true,
        ]);
    }
    
    /**
     * Test user cannot update someone else's message.
     */
    public function test_user_cannot_update_someone_elses_message(): void
    {
        $otherUser = User::factory()->create();
        $this->conversation->users()->attach($otherUser->id);
        
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson("/api/messages/{$message->id}", [
            'content' => 'This should not update',
        ]);
        
        $response->assertStatus(403);
    }
    
    /**
     * Test user can delete their own message.
     */
    public function test_user_can_delete_their_own_message(): void
    {
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/messages/{$message->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }
    
    /**
     * Test admin can delete someone else's message.
     */
    public function test_admin_can_delete_someone_elses_message(): void
    {
        // Set the user as an admin in the conversation
        $this->conversation->users()->updateExistingPivot($this->user->id, [
            'role' => 'admin',
        ]);
        
        $otherUser = User::factory()->create();
        $this->conversation->users()->attach($otherUser->id);
        
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/messages/{$message->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }
    
    /**
     * Test regular member cannot delete someone else's message.
     */
    public function test_regular_member_cannot_delete_someone_elses_message(): void
    {
        $otherUser = User::factory()->create();
        $this->conversation->users()->attach($otherUser->id);
        
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $otherUser->id,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/messages/{$message->id}");
        
        $response->assertStatus(403);
    }
    
    /**
     * Test user can get messages after a timestamp.
     */
    public function test_user_can_get_messages_after_timestamp(): void
    {
        // Create some old messages
        Message::factory()->count(3)->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);
        
        // Get the current timestamp
        $timestamp = now()->format('Y-m-d H:i:s');
        
        // Create some new messages
        Message::factory()->count(2)->create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'created_at' => now()->addHour(),
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/conversations/{$this->conversation->id}/messages/after?timestamp={$timestamp}");
        
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }
}
