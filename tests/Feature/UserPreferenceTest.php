<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class UserPreferenceTest
 * 
 * Feature tests for UserPreference operations
 */
class UserPreferenceTest extends TestCase
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
    public function test_user_can_list_preferences()
    {
        // Arrange
        UserPreference::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/user-preferences');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'key', 'value', 'created_at', 'updated_at']
                ]
            ]);
    }

    /** @test */
    public function test_user_can_create_preference()
    {
        // Arrange
        $preferenceData = [
            'user_id' => $this->user->id,
            'key' => 'theme',
            'value' => 'dark'
        ];

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user-preferences', $preferenceData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User preference created successfully.',
                'data' => [
                    'user_id' => $this->user->id,
                    'key' => 'theme',
                    'value' => 'dark'
                ]
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'key' => 'theme',
            'value' => 'dark'
        ]);
    }

    /** @test */
    public function test_user_can_view_preference()
    {
        // Arrange
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/user-preferences/{$preference->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $preference->id,
                    'user_id' => $preference->user_id,
                    'key' => $preference->key,
                    'value' => $preference->value
                ]
            ]);
    }

    /** @test */
    public function test_user_can_update_preference()
    {
        // Arrange
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'theme',
            'value' => 'light'
        ]);

        $updateData = ['value' => 'dark'];

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/user-preferences/{$preference->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User preference updated successfully.',
                'data' => [
                    'id' => $preference->id,
                    'value' => 'dark'
                ]
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'id' => $preference->id,
            'value' => 'dark'
        ]);
    }

    /** @test */
    public function test_user_can_delete_preference()
    {
        // Arrange
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/user-preferences/{$preference->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User preference deleted successfully.'
            ]);

        $this->assertDatabaseMissing('user_preferences', [
            'id' => $preference->id
        ]);
    }

    /** @test */
    public function test_user_can_get_their_preferences()
    {
        // Arrange
        $preferences = UserPreference::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/users/{$this->user->id}/preferences");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_set_preference()
    {
        // Arrange
        $preferenceData = [
            'key' => 'language',
            'value' => 'es'
        ];

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/users/{$this->user->id}/preferences", $preferenceData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User preference set successfully.',
                'data' => [
                    'user_id' => $this->user->id,
                    'key' => 'language',
                    'value' => 'es'
                ]
            ]);
    }

    /** @test */
    public function test_user_can_bulk_set_preferences()
    {
        // Arrange
        $preferences = [
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/users/{$this->user->id}/preferences/bulk", [
                'preferences' => $preferences
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User preferences set successfully.'
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_validation_fails_for_invalid_data()
    {
        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user-preferences', [
                'user_id' => 'invalid',
                'key' => '',
                'value' => ''
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'key', 'value']);
    }

    /** @test */
    public function test_unique_constraint_is_enforced()
    {
        // Arrange
        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'theme'
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user-preferences', [
                'user_id' => $this->user->id,
                'key' => 'theme',
                'value' => 'dark'
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    /** @test */
    public function test_unauthorized_user_cannot_access_others_preferences()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $preference = UserPreference::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/user-preferences/{$preference->id}");

        // Assert
        $response->assertStatus(403);
    }
}
