<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Add the user to the workspace
        $this->workspace->users()->attach($this->user->id);
    }

    /**
     * Test user can list their conversations.
     */
    public function test_user_can_list_conversations(): void
    {
        // Create conversations and add the user to them
        $conversation1 = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        $conversation1->users()->attach($this->user->id, ['role' => 'member']);
        
        $conversation2 = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        $conversation2->users()->attach($this->user->id, ['role' => 'member']);
        
        // Create a conversation that the user is not part of
        Conversation::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/conversations?workspace_id=' . $this->workspace->id);
        
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }
    
    /**
     * Test user can create a conversation.
     */
    public function test_user_can_create_conversation(): void
    {
        Sanctum::actingAs($this->user);
        
        $otherUsers = User::factory()->count(2)->create();
        
        $conversationData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Conversation',
            'type' => 'group',
            'is_private' => true,
            'user_ids' => [$this->user->id, ...$otherUsers->pluck('id')->toArray()],
        ];
        
        $response = $this->postJson('/api/conversations', $conversationData);
        
        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'Test Conversation',
                    'type' => 'group',
                    'is_private' => true,
                ]);
                
        $conversationId = $response->json('data.id');
        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'workspace_id' => $this->workspace->id,
        ]);
        
        // Check that users were added to the conversation
        foreach ($conversationData['user_ids'] as $userId) {
            $this->assertDatabaseHas('conversation_user', [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
            ]);
        }
    }
    
    /**
     * Test user can view a conversation they are part of.
     */
    public function test_user_can_view_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $conversation->users()->attach($this->user->id, ['role' => 'member']);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/conversations/{$conversation->id}");
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $conversation->id,
                    'name' => $conversation->name,
                ]);
    }
    
    /**
     * Test user cannot view a conversation they are not part of.
     */
    public function test_user_cannot_view_conversation_they_are_not_part_of(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        // Not adding the user to this conversation
        
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/conversations/{$conversation->id}");
        
        $response->assertStatus(403);
    }
    
    /**
     * Test admin can update conversation.
     */
    public function test_admin_can_update_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Name',
        ]);
        $conversation->users()->attach($this->user->id, ['role' => 'admin']);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson("/api/conversations/{$conversation->id}", [
            'name' => 'Updated Name',
        ]);
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'name' => 'Updated Name',
                ]);
                
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'name' => 'Updated Name',
        ]);
    }
    
    /**
     * Test regular member cannot update conversation.
     */
    public function test_regular_member_cannot_update_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $conversation->users()->attach($this->user->id, ['role' => 'member']);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson("/api/conversations/{$conversation->id}", [
            'name' => 'Updated Name',
        ]);
        
        $response->assertStatus(403);
    }
    
    /**
     * Test owner can delete conversation.
     */
    public function test_owner_can_delete_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $conversation->users()->attach($this->user->id, ['role' => 'owner']);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/conversations/{$conversation->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);
    }
    
    /**
     * Test non-owner cannot delete conversation.
     */
    public function test_non_owner_cannot_delete_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $conversation->users()->attach($this->user->id, ['role' => 'admin']);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson("/api/conversations/{$conversation->id}");
        
        $response->assertStatus(403);
        
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
        ]);
    }
    
    /**
     * Test admin can add users to conversation.
     */
    public function test_admin_can_add_users_to_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $conversation->users()->attach($this->user->id, ['role' => 'admin']);
        
        $newUser = User::factory()->create();
        
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/conversations/{$conversation->id}/users", [
            'user_ids' => [$newUser->id],
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $newUser->id,
        ]);
    }
    
    /**
     * Test direct conversation creation between users.
     */
    public function test_create_direct_conversation(): void
    {
        $otherUser = User::factory()->create();
        
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/conversations/direct", [
            'workspace_id' => $this->workspace->id,
            'user_id' => $otherUser->id,
        ]);
        
        $response->assertStatus(201);
        
        $conversationId = $response->json('data.id');
        
        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'workspace_id' => $this->workspace->id,
            'type' => 'direct',
        ]);
        
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->user->id,
        ]);
        
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversationId,
            'user_id' => $otherUser->id,
        ]);
    }
    
    /**
     * Test marking a conversation as read.
     */
    public function test_mark_conversation_as_read(): void
    {
        $conversation = Conversation::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $conversation->users()->attach($this->user->id, [
            'role' => 'member',
            'last_read_at' => null,
        ]);
        
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/conversations/{$conversation->id}/read");
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->user->id,
            'last_read_at' => now()->toDateTimeString(),
        ]);
    }
}
