<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class SubscriptionTest
 * 
 * Feature tests for subscription API endpoints
 * 
 * @package Tests\Feature
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * @var Plan
     */
    protected $plan;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->plan = Plan::factory()->create();
    }

    /**
     * Test user can list subscriptions.
     */
    public function test_user_can_list_subscriptions(): void
    {
        Subscription::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/subscriptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'plan_id',
                        'stripe_id',
                        'stripe_status',
                        'quantity',
                        'is_active',
                        'is_on_trial',
                        'has_ended',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create subscription.
     */
    public function test_user_can_create_subscription(): void
    {
        $subscriptionData = [
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'quantity' => 2,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/subscriptions', $subscriptionData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'workspace_id' => $this->workspace->id,
                'plan_id' => $this->plan->id,
                'quantity' => 2,
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
            'stripe_id' => 'sub_test123',
        ]);
    }

    /**
     * Test user can view subscription.
     */
    public function test_user_can_view_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/subscriptions/{$subscription->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $subscription->id,
                'workspace_id' => $this->workspace->id,
                'plan_id' => $this->plan->id,
            ]);
    }

    /**
     * Test user can update subscription.
     */
    public function test_user_can_update_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
            'quantity' => 1,
        ]);

        $updateData = [
            'quantity' => 3,
            'stripe_status' => 'active',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/subscriptions/{$subscription->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'quantity' => 3,
                'stripe_status' => 'active',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'quantity' => 3,
            'stripe_status' => 'active',
        ]);
    }

    /**
     * Test user can delete subscription.
     */
    public function test_user_can_delete_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/subscriptions/{$subscription->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    /**
     * Test user can activate subscription.
     */
    public function test_user_can_activate_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
            'stripe_status' => 'inactive',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/subscriptions/{$subscription->id}/activate");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'stripe_status' => 'active',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'active',
        ]);
    }

    /**
     * Test user can deactivate subscription.
     */
    public function test_user_can_deactivate_subscription(): void
    {
        $subscription = Subscription::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/subscriptions/{$subscription->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'stripe_status' => 'inactive',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'inactive',
        ]);
    }

    /**
     * Test user can update subscription quantity.
     */
    public function test_user_can_update_subscription_quantity(): void
    {
        $subscription = Subscription::factory()->create([
            'workspace_id' => $this->workspace->id,
            'plan_id' => $this->plan->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/subscriptions/{$subscription->id}/quantity", [
                'quantity' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'quantity' => 5,
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Test validation errors for subscription creation.
     */
    public function test_subscription_creation_validation_errors(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/subscriptions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id', 'plan_id']);
    }

    /**
     * Test unauthorized access to subscriptions.
     */
    public function test_unauthorized_access_to_subscriptions(): void
    {
        $response = $this->getJson('/api/subscriptions');

        $response->assertStatus(401);
    }
}
