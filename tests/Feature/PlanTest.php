<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Plan Feature Tests
 * 
 * Tests HTTP endpoints for plan management
 */
class PlanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Test user can list plans
     */
    public function test_user_can_list_plans(): void
    {
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'price',
                        'billing_cycle',
                        'limits',
                        'features',
                        'is_active',
                        'is_popular',
                        'status',
                        'billing_cycle_label',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test user can create plan
     */
    public function test_user_can_create_plan(): void
    {
        $planData = [
            'name' => 'Test Professional Plan',
            'slug' => 'test-professional',
            'description' => 'A test professional plan',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'max_users' => 10,
            'max_projects' => 50,
            'max_storage_gb' => 100,
            'features' => ['API Access', 'Priority Support'],
            'is_active' => true,
            'is_popular' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/plans', $planData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Test Professional Plan',
                    'slug' => 'test-professional',
                    'price' => ['amount' => 29.99],
                    'billing_cycle' => 'monthly',
                ]
            ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'Test Professional Plan',
            'slug' => 'test-professional',
            'price' => 29.99,
        ]);
    }

    /**
     * Test user can view plan
     */
    public function test_user_can_view_plan(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/plans/{$plan->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ]
            ]);
    }

    /**
     * Test user can update plan
     */
    public function test_user_can_update_plan(): void
    {
        $plan = Plan::factory()->create();

        $updateData = [
            'name' => 'Updated Plan Name',
            'price' => 39.99,
            'is_popular' => true,
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/api/plans/{$plan->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'name' => 'Updated Plan Name',
                    'price' => ['amount' => 39.99],
                    'is_popular' => true,
                ]
            ]);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Updated Plan Name',
            'price' => 39.99,
            'is_popular' => true,
        ]);
    }

    /**
     * Test user can delete plan
     */
    public function test_user_can_delete_plan(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/plans/{$plan->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Plan deleted successfully'
            ]);

        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }

    /**
     * Test user can activate plan
     */
    public function test_user_can_activate_plan(): void
    {
        $plan = Plan::factory()->inactive()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/plans/{$plan->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'is_active' => true,
                    'status' => 'active',
                ]
            ]);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test user can deactivate plan
     */
    public function test_user_can_deactivate_plan(): void
    {
        $plan = Plan::factory()->active()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/plans/{$plan->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'is_active' => false,
                    'status' => 'inactive',
                ]
            ]);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test user can mark plan as popular
     */
    public function test_user_can_mark_plan_as_popular(): void
    {
        $plan = Plan::factory()->create(['is_popular' => false]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/plans/{$plan->id}/mark-popular");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'is_popular' => true,
                ]
            ]);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_popular' => true,
        ]);
    }

    /**
     * Test user can remove popular status
     */
    public function test_user_can_remove_popular_status(): void
    {
        $plan = Plan::factory()->popular()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/plans/{$plan->id}/remove-popular");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'is_popular' => false,
                ]
            ]);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_popular' => false,
        ]);
    }

    /**
     * Test get active plans only
     */
    public function test_can_get_active_plans_only(): void
    {
        Plan::factory()->active()->count(2)->create();
        Plan::factory()->inactive()->count(1)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/plans/active');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $plan) {
            $this->assertTrue($plan['is_active']);
        }
    }

    /**
     * Test get popular plans only
     */
    public function test_can_get_popular_plans_only(): void
    {
        Plan::factory()->popular()->active()->count(2)->create();
        Plan::factory()->active()->count(1)->create(['is_popular' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/plans/popular');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $plan) {
            $this->assertTrue($plan['is_popular']);
        }
    }

    /**
     * Test find plan by slug
     */
    public function test_can_find_plan_by_slug(): void
    {
        $plan = Plan::factory()->create(['slug' => 'test-professional-plan']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/plans/slug/test-professional-plan');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plan->id,
                    'slug' => 'test-professional-plan',
                ]
            ]);
    }

    /**
     * Test validation errors on create
     */
    public function test_create_plan_validation_errors(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/plans', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'price', 'billing_cycle']);
    }

    /**
     * Test unique slug validation
     */
    public function test_plan_slug_must_be_unique(): void
    {
        Plan::factory()->create(['slug' => 'existing-plan']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plans', [
                'name' => 'New Plan',
                'slug' => 'existing-plan',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /**
     * Test filtering plans
     */
    public function test_can_filter_plans(): void
    {
        Plan::factory()->monthly()->count(2)->create();
        Plan::factory()->yearly()->count(1)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/plans?billing_cycle=monthly');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $plan) {
            $this->assertEquals('monthly', $plan['billing_cycle']);
        }
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access_denied(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->getJson("/api/plans/{$plan->id}");

        $response->assertStatus(401);
    }
}
