<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Goal Feature Tests
 * 
 * Tests all goal-related API endpoints and functionality
 */
class GoalTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Authenticated user for testing
     *
     * @var User
     */
    protected User $user;

    /**
     * Test workspace
     *
     * @var Workspace
     */
    protected Workspace $workspace;

    /**
     * Test team
     *
     * @var Team
     */
    protected Team $team;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test workspace (assuming you have these models)
        $this->workspace = Workspace::factory()->create();
        
        // Create test team
        $this->team = Team::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        // Authenticate user
        $this->actingAs($this->user, 'sanctum');
    }

    /**
     * Test user can list goals
     */
    public function test_user_can_list_goals(): void
    {
        // Create test goals
        Goal::factory(5)->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/goals');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'workspace_id',
                            'team_id',
                            'owner_id',
                            'name',
                            'description',
                            'status',
                            'start_date',
                            'end_date',
                            'progress',
                            'metadata',
                            'created_at',
                            'updated_at',
                            'is_active',
                            'is_completed',
                            'is_cancelled',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /**
     * Test user can create goal
     */
    public function test_user_can_create_goal(): void
    {
        $goalData = [
            'workspace_id' => $this->workspace->id,
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
            'name' => 'Test Goal',
            'description' => 'This is a test goal',
            'status' => 'draft',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'progress' => 0,
            'metadata' => ['priority' => 'high']
        ];

        $response = $this->postJson('/api/goals', $goalData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'Test Goal',
                    'status' => 'draft',
                    'progress' => 0
                ]);

        $this->assertDatabaseHas('goals', [
            'name' => 'Test Goal',
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id
        ]);
    }

    /**
     * Test user can view specific goal
     */
    public function test_user_can_view_goal(): void
    {
        $goal = Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/goals/{$goal->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'workspace_id' => $goal->workspace_id
                ]);
    }

    /**
     * Test user can update goal
     */
    public function test_user_can_update_goal(): void
    {
        $goal = Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $updateData = [
            'name' => 'Updated Goal Name',
            'status' => 'active',
            'progress' => 25
        ];

        $response = $this->putJson("/api/goals/{$goal->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'name' => 'Updated Goal Name',
                    'status' => 'active',
                    'progress' => 25
                ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'name' => 'Updated Goal Name',
            'status' => 'active',
            'progress' => 25
        ]);
    }

    /**
     * Test user can delete goal
     */
    public function test_user_can_delete_goal(): void
    {
        $goal = Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/goals/{$goal->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Goal deleted successfully'
                ]);

        $this->assertDatabaseMissing('goals', [
            'id' => $goal->id
        ]);
    }

    /**
     * Test user can activate goal
     */
    public function test_user_can_activate_goal(): void
    {
        $goal = Goal::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/goals/{$goal->id}/activate");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'status' => 'active'
                ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'status' => 'active'
        ]);
    }

    /**
     * Test user can complete goal
     */
    public function test_user_can_complete_goal(): void
    {
        $goal = Goal::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/goals/{$goal->id}/complete");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'status' => 'completed',
                    'progress' => 100
                ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'status' => 'completed',
            'progress' => 100
        ]);
    }

    /**
     * Test user can cancel goal
     */
    public function test_user_can_cancel_goal(): void
    {
        $goal = Goal::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/goals/{$goal->id}/cancel");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'status' => 'cancelled'
                ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'status' => 'cancelled'
        ]);
    }

    /**
     * Test user can update goal progress
     */
    public function test_user_can_update_goal_progress(): void
    {
        $goal = Goal::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'progress' => 0
        ]);

        $response = $this->patchJson("/api/goals/{$goal->id}/progress", [
            'progress' => 75
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'progress' => 75
                ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'progress' => 75
        ]);
    }

    /**
     * Test user can assign user to goal
     */
    public function test_user_can_assign_user_to_goal(): void
    {
        $newOwner = User::factory()->create();
        $goal = Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/goals/{$goal->id}/assign-user", [
            'user_id' => $newOwner->id
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'owner_id' => $newOwner->id
                ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'owner_id' => $newOwner->id
        ]);
    }

    /**
     * Test goal creation validation
     */
    public function test_goal_creation_validation(): void
    {
        $response = $this->postJson('/api/goals', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'workspace_id',
                    'name'
                ]);
    }

    /**
     * Test user cannot view goal they don't have access to
     */
    public function test_user_cannot_view_unauthorized_goal(): void
    {
        $otherUser = User::factory()->create();
        $goal = Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/goals/{$goal->id}");

        $response->assertStatus(403);
    }

    /**
     * Test user cannot update goal they don't own
     */
    public function test_user_cannot_update_unauthorized_goal(): void
    {
        $otherUser = User::factory()->create();
        $goal = Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/goals/{$goal->id}", [
            'name' => 'Hacked Goal'
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test cannot activate already completed goal
     */
    public function test_cannot_activate_completed_goal(): void
    {
        $goal = Goal::factory()->completed()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/goals/{$goal->id}/activate");

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'error' => 'Cannot activate a completed goal.'
                ]);
    }

    /**
     * Test progress validation
     */
    public function test_progress_validation(): void
    {
        $goal = Goal::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        // Test invalid progress values
        $response = $this->patchJson("/api/goals/{$goal->id}/progress", [
            'progress' => 150
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['progress']);

        $response = $this->patchJson("/api/goals/{$goal->id}/progress", [
            'progress' => -10
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['progress']);
    }

    /**
     * Test goal filtering by status
     */
    public function test_can_filter_goals_by_status(): void
    {
        Goal::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        Goal::factory()->completed()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/goals?status=active');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['status']);
    }

    /**
     * Test goal search functionality
     */
    public function test_can_search_goals(): void
    {
        Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'name' => 'Important Goal'
        ]);

        Goal::factory()->create([
            'workspace_id' => $this->workspace->id,
            'owner_id' => $this->user->id,
            'name' => 'Regular Task'
        ]);

        $response = $this->getJson('/api/goals?search=Important');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Important', $data[0]['name']);
    }
}