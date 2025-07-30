<?php
// tests/Feature/ReactionTest.php

namespace Tests\Feature;

use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Reaction Feature Tests
 * 
 * Tests all API endpoints and functionality for reactions
 */
class ReactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Authenticate user for all requests
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user can list reactions.
     */
    public function test_user_can_list_reactions(): void
    {
        Reaction::factory()->count(5)->create();

        $response = $this->getJson('/api/reactions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'reactable_type',
                            'reactable_id',
                            'created_at',
                            'user',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /**
     * Test user can create reaction.
     */
    public function test_user_can_create_reaction(): void
    {
        $reactionData = [
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ];

        $response = $this->postJson('/api/reactions', $reactionData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'type',
                        'reactable_type',
                        'reactable_id',
                        'user_id',
                    ]
                ]);

        $this->assertDatabaseHas('reactions', [
            'type' => 'thumbs_up',
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can view single reaction.
     */
    public function test_user_can_view_reaction(): void
    {
        $reaction = Reaction::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/reactions/{$reaction->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'type',
                        'reactable_type',
                        'user',
                        'reactable',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $reaction->id,
                        'type' => $reaction->type,
                    ]
                ]);
    }

    /**
     * Test user can update reaction.
     */
    public function test_user_can_update_reaction(): void
    {
        $reaction = Reaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'thumbs_up',
        ]);

        $updateData = [
            'type' => 'heart',
        ];

        $response = $this->putJson("/api/reactions/{$reaction->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'type' => 'heart',
                    ]
                ]);

        $this->assertDatabaseHas('reactions', [
            'id' => $reaction->id,
            'type' => 'heart',
        ]);
    }

    /**
     * Test user can delete reaction.
     */
    public function test_user_can_delete_reaction(): void
    {
        $reaction = Reaction::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/reactions/{$reaction->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Reaction deleted successfully.'
                ]);

        $this->assertDatabaseMissing('reactions', [
            'id' => $reaction->id,
        ]);
    }

    /**
     * Test toggle reaction functionality.
     */
    public function test_toggle_reaction(): void
    {
        $toggleData = [
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ];

        // First toggle - should create reaction
        $response = $this->postJson('/api/reactions/toggle', $toggleData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Reaction added successfully.',
                    'action' => 'added',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'type',
                        'user_id',
                    ]
                ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ]);

        // Second toggle - should remove reaction
        $response = $this->postJson('/api/reactions/toggle', $toggleData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Reaction removed successfully.',
                    'action' => 'removed',
                    'data' => null,
                ]);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ]);
    }

    /**
     * Test toggle reaction with different type updates existing.
     */
    public function test_toggle_reaction_updates_existing_different_type(): void
    {
        // Create existing reaction
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ]);

        // Toggle with different type
        $toggleData = [
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'heart',
        ];

        $response = $this->postJson('/api/reactions/toggle', $toggleData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Reaction added successfully.',
                    'action' => 'added',
                ]);

        // Should have updated the existing reaction
        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'heart',
        ]);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ]);
    }

    /**
     * Test get item reactions.
     */
    public function test_get_item_reactions(): void
    {
        $reactableType = 'App\\Models\\Post';
        $reactableId = 1;

        Reaction::factory()->count(3)->create([
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
        ]);

        $response = $this->getJson('/api/reactions/item', [
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'user',
                        ]
                    ]
                ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /**
     * Test get reaction summary.
     */
    public function test_get_reaction_summary(): void
    {
        $reactableType = 'App\\Models\\Post';
        $reactableId = 1;

        // Create different types of reactions
        Reaction::factory()->count(2)->create([
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
            'type' => 'thumbs_up',
        ]);

        Reaction::factory()->create([
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
            'type' => 'heart',
        ]);

        // Create user's reaction
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
            'type' => 'thumbs_up',
        ]);

        $response = $this->getJson('/api/reactions/summary', [
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'counts',
                        'total',
                        'user_reaction',
                        'available_types',
                    ]
                ]);

        $data = $response->json('data');
        $this->assertEquals(4, $data['total']);
        $this->assertEquals('thumbs_up', $data['user_reaction']);
        $this->assertEquals(3, $data['counts']['thumbs_up']);
        $this->assertEquals(1, $data['counts']['heart']);
    }

    /**
     * Test get user reactions.
     */
    public function test_get_user_reactions(): void
    {
        Reaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create reactions for other users
        Reaction::factory()->count(2)->create();

        $response = $this->getJson('/api/reactions/user');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'reactable',
                        ]
                    ]
                ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /**
     * Test bulk toggle reactions.
     */
    public function test_bulk_toggle_reactions(): void
    {
        $items = [
            ['reactable_type' => 'App\\Models\\Post', 'reactable_id' => 1],
            ['reactable_type' => 'App\\Models\\Post', 'reactable_id' => 2],
        ];

        $response = $this->postJson('/api/reactions/bulk-toggle', [
            'items' => $items,
            'type' => 'thumbs_up',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        '*' => [
                            'reactable_type',
                            'reactable_id',
                            'reaction',
                        ]
                    ]
                ]);

        // Should have created reactions for both items
        foreach ($items as $item) {
            $this->assertDatabaseHas('reactions', [
                'user_id' => $this->user->id,
                'reactable_type' => $item['reactable_type'],
                'reactable_id' => $item['reactable_id'],
                'type' => 'thumbs_up',
            ]);
        }
    }   


    /**
     * Test bulk toggle reactions with existing reactions.
     */
    public function test_bulk_toggle_reactions_with_existing(): void
    {
        $items = [
            ['reactable_type' => 'App\\Models\\Post', 'reactable_id' => 1],
            ['reactable_type' => 'App\\Models\\Post', 'reactable_id' => 2],
        ];

        // Create existing reactions for the first item
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'thumbs_up',
        ]);

        $response = $this->postJson('/api/reactions/bulk-toggle', [
            'items' => $items,
            'type' => 'heart',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        '*' => [
                            'reactable_type',
                            'reactable_id',
                            'reaction',
                        ]
                    ]
                ]);

        // Should have updated the existing reaction to heart
        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
            'type' => 'heart',
        ]);

        // Should have created new reaction for second item
        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 2,
            'type' => 'heart',
        ]);
    }
}