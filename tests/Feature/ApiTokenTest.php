<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class ApiTokenTest
 * 
 * Feature tests for ApiToken operations
 */
class ApiTokenTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function test_user_can_list_tokens()
    {
        // Arrange
        ApiToken::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/api-tokens');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'name', 'abilities', 'is_active', 'created_at']
                ]
            ]);
    }

    /** @test */
    public function test_user_can_create_token()
    {
        // Arrange
        $tokenData = [
            'user_id' => $this->user->id,
            'name' => 'Test Token',
            'abilities' => ['read', 'write']
        ];

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/api-tokens', $tokenData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'API token created successfully.',
                'data' => [
                    'user_id' => $this->user->id,
                    'name' => 'Test Token',
                    'abilities' => ['read', 'write']
                ]
            ])
            ->assertJsonStructure([
                'data' => ['token'] // Token should be visible on creation
            ]);

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $this->user->id,
            'name' => 'Test Token'
        ]);
    }

    /** @test */
    public function test_user_can_view_token()
    {
        // Arrange
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/api-tokens/{$token->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $token->id,
                    'user_id' => $token->user_id,
                    'name' => $token->name
                ]
            ]);
    }

    /** @test */
    public function test_user_can_update_token()
    {
        // Arrange
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name'
        ]);

        $updateData = ['name' => 'New Name'];

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/api-tokens/{$token->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'API token updated successfully.',
                'data' => [
                    'id' => $token->id,
                    'name' => 'New Name'
                ]
            ]);

        $this->assertDatabaseHas('api_tokens', [
            'id' => $token->id,
            'name' => 'New Name'
        ]);
    }

    /** @test */
    public function test_user_can_delete_token()
    {
        // Arrange
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/api-tokens/{$token->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'API token deleted successfully.'
            ]);

        $this->assertDatabaseMissing('api_tokens', [
            'id' => $token->id
        ]);
    }

    /** @test */
    public function test_user_can_revoke_token()
    {
        // Arrange
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/api-tokens/{$token->id}/revoke");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'API token revoked successfully.'
            ]);

        $this->assertDatabaseMissing('api_tokens', [
            'id' => $token->id
        ]);
    }

    /** @test */
    public function test_user_can_get_their_tokens()
    {
        // Arrange
        $tokens = ApiToken::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/users/{$this->user->id}/tokens");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_get_active_tokens()
    {
        // Arrange
        ApiToken::factory()->count(2)->active()->create([
            'user_id' => $this->user->id
        ]);
        ApiToken::factory()->count(1)->expired()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/users/{$this->user->id}/tokens/active");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_user_can_revoke_all_tokens()
    {
        // Arrange
        ApiToken::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/users/{$this->user->id}/tokens/revoke-all");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
            'message' => 'All API tokens for user revoked successfully. 3 tokens were revoked.'
        ]);

        $this->assertDatabaseCount('api_tokens', 0);
    }

    /** @test */
    public function test_admin_can_cleanup_expired_tokens()
    {
        // Arrange
        ApiToken::factory()->count(2)->active()->create();
        ApiToken::factory()->count(3)->expired()->create();

        // Act
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/api-tokens/cleanup');

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Expired tokens cleaned up successfully. 3 tokens were removed.'
            ]);

        $this->assertDatabaseCount('api_tokens', 2);
    }

    /** @test */
    public function test_validation_fails_for_invalid_token_data()
    {
        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/api-tokens', [
                'user_id' => 'invalid',
                'name' => '',
                'expires_at' => 'invalid-date'
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'name', 'expires_at']);
    }

    /** @test */
    public function test_token_expiration_validation()
    {
        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/api-tokens', [
                'user_id' => $this->user->id,
                'name' => 'Test Token',
                'expires_at' => '2020-01-01 00:00:00' // Past date
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    /** @test */
    public function test_unauthorized_user_cannot_access_others_tokens()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $token = ApiToken::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/api-tokens/{$token->id}");

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function test_token_is_hidden_in_normal_responses()
    {
        // Arrange
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/api-tokens/{$token->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonMissing(['token' => $token->token]);
    }
}
