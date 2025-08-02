<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * WebhookDelivery Feature Tests
 * 
 * Tests webhook delivery API endpoints and functionality
 */
class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Webhook $webhook;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->webhook = Webhook::factory()->create();
        
        // Assume you have permissions system
        $this->user->givePermissionTo([
            'webhook_deliveries.view',
            'webhook_deliveries.create',
            'webhook_deliveries.update',
            'webhook_deliveries.delete',
            'webhook_deliveries.retry',
        ]);
    }

    /**
     * Test user can list webhook deliveries
     */
    public function test_user_can_list_webhook_deliveries(): void
    {
        // Arrange
        WebhookDelivery::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/webhook-deliveries');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'webhook_id',
                        'event',
                        'payload',
                        'status',
                        'attempts',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create webhook delivery
     */
    public function test_user_can_create_webhook_delivery(): void
    {
        // Arrange
        $webhookDeliveryData = [
            'webhook_id' => $this->webhook->id,
            'event' => 'user.created',
            'payload' => [
                'user_id' => 123,
                'email' => 'test@example.com',
            ],
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/webhook-deliveries', $webhookDeliveryData);

        // Assert
        $response->assertCreated()
            ->assertJsonFragment([
                'webhook_id' => $this->webhook->id,
                'event' => 'user.created',
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $this->webhook->id,
            'event' => 'user.created',
        ]);
    }

    /**
     * Test user can view webhook delivery
     */
    public function test_user_can_view_webhook_delivery(): void
    {
        // Arrange
        $webhookDelivery = WebhookDelivery::factory()->create([
            'webhook_id' => $this->webhook->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/webhook-deliveries/{$webhookDelivery->id}");

        // Assert
        $response->assertOk()
            ->assertJsonFragment([
                'id' => $webhookDelivery->id,
                'webhook_id' => $this->webhook->id,
            ]);
    }

    /**
     * Test user can update webhook delivery
     */
    public function test_user_can_update_webhook_delivery(): void
    {
        // Arrange
        $webhookDelivery = WebhookDelivery::factory()->create([
            'webhook_id' => $this->webhook->id,
        ]);
        
        $updateData = [
            'event' => 'user.updated',
            'status' => 'delivered',
            'response_code' => 200,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/webhook-deliveries/{$webhookDelivery->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJsonFragment([
                'event' => 'user.updated',
                'status' => 'delivered',
                'response_code' => 200,
            ]);

        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $webhookDelivery->id,
            'event' => 'user.updated',
            'status' => 'delivered',
        ]);
    }

    /**
     * Test user can delete webhook delivery
     */
    public function test_user_can_delete_webhook_delivery(): void
    {
        // Arrange
        $webhookDelivery = WebhookDelivery::factory()->create([
            'webhook_id' => $this->webhook->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/webhook-deliveries/{$webhookDelivery->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Webhook delivery deleted successfully'
            ]);

        $this->assertSoftDeleted('webhook_deliveries', [
            'id' => $webhookDelivery->id,
        ]);
    }

    /**
     * Test user can retry failed webhook delivery
     */
    public function test_user_can_retry_failed_webhook_delivery(): void
    {
        // Arrange
        $webhookDelivery = WebhookDelivery::factory()->failed()->create([
            'webhook_id' => $this->webhook->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/webhook-deliveries/{$webhookDelivery->id}/retry");

        // Assert
        $response->assertOk();
        
        $webhookDelivery->refresh();
        $this->assertTrue($webhookDelivery->attempts > 0);
    }

    /**
     * Test user cannot retry non-failed webhook delivery
     */
    public function test_user_cannot_retry_non_failed_webhook_delivery(): void
    {
        // Arrange
        $webhookDelivery = WebhookDelivery::factory()->delivered()->create([
            'webhook_id' => $this->webhook->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/webhook-deliveries/{$webhookDelivery->id}/retry");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Only failed deliveries can be retried'
            ]);
    }

    /**
     * Test webhook delivery validation
     */
    public function test_webhook_delivery_validation(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/webhook-deliveries', []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['webhook_id', 'event', 'payload']);
    }

    /**
     * Test filtering webhook deliveries by status
     */
    public function test_user_can_filter_webhook_deliveries_by_status(): void
    {
        // Arrange
        WebhookDelivery::factory()->delivered()->count(3)->create();
        WebhookDelivery::factory()->failed()->count(2)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/webhook-deliveries?status=delivered');

        // Assert
        $response->assertOk();
        $this->assertEquals(3, count($response->json('data')));
    }

    /**
     * Test webhook delivery statistics
     */
    public function test_user_can_get_webhook_delivery_statistics(): void
    {
        // Arrange
        WebhookDelivery::factory()->delivered()->count(5)->create();
        WebhookDelivery::factory()->failed()->count(2)->create();
        WebhookDelivery::factory()->pending()->count(1)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/webhook-deliveries/stats');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'delivered',
                    'failed',
                    'pending',
                    'success_rate',
                ]
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_user_cannot_access_webhook_deliveries(): void
    {
        // Act
        $response = $this->getJson('/api/webhook-deliveries');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Test processing failed deliveries
     */
    public function test_user_can_process_failed_deliveries(): void
    {
        // Arrange
        $this->user->givePermissionTo('webhook_deliveries.process_failed');
        WebhookDelivery::factory()->readyForRetry()->count(3)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/webhook-deliveries/process-failed');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'processed_count'
            ]);
    }
}
