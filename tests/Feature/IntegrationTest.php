<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Integration Feature Tests
 * 
 * Tests HTTP endpoints for integration management
 */
class IntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
    }

    /**
     * Test user can list integrations
     */
    public function test_user_can_list_integrations(): void
    {
        Integration::factory()
            ->count(3)
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/integrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'name',
                        'type',
                        'configuration',
                        'is_active',
                        'status',
                        'type_label',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test user can create integration
     */
    public function test_user_can_create_integration(): void
    {
        $integrationData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Slack Integration',
            'type' => 'slack',
            'configuration' => [
                'api_key' => 'test-api-key',
                'webhook_url' => 'https://hooks.slack.com/test',
                'channel' => '#general',
            ],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/integrations', $integrationData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Test Slack Integration',
                    'type' => 'slack',
                    'workspace_id' => $this->workspace->id,
                    'is_active' => true,
                ]
            ]);

        $this->assertDatabaseHas('integrations', [
            'name' => 'Test Slack Integration',
            'type' => 'slack',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    /**
     * Test user can view integration
     */
    public function test_user_can_view_integration(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/integrations/{$integration->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $integration->id,
                    'name' => $integration->name,
                    'type' => $integration->type,
                    'workspace_id' => $this->workspace->id,
                ]
            ]);
    }

    /**
     * Test user can update integration
     */
    public function test_user_can_update_integration(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->create();

        $updateData = [
            'name' => 'Updated Integration Name',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/api/integrations/{$integration->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $integration->id,
                    'name' => 'Updated Integration Name',
                    'is_active' => false,
                ]
            ]);

        $this->assertDatabaseHas('integrations', [
            'id' => $integration->id,
            'name' => 'Updated Integration Name',
            'is_active' => false,
        ]);
    }

    /**
     * Test user can delete integration
     */
    public function test_user_can_delete_integration(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/integrations/{$integration->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Integration deleted successfully'
            ]);

        $this->assertDatabaseMissing('integrations', [
            'id' => $integration->id,
        ]);
    }

    /**
     * Test user can activate integration
     */
    public function test_user_can_activate_integration(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->inactive()
            ->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/integrations/{$integration->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $integration->id,
                    'is_active' => true,
                    'status' => 'active',
                ]
            ]);

        $this->assertDatabaseHas('integrations', [
            'id' => $integration->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test user can deactivate integration
     */
    public function test_user_can_deactivate_integration(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->active()
            ->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/integrations/{$integration->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $integration->id,
                    'is_active' => false,
                    'status' => 'inactive',
                ]
            ]);

        $this->assertDatabaseHas('integrations', [
            'id' => $integration->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test user can test integration connection
     */
    public function test_user_can_test_integration_connection(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/integrations/{$integration->id}/test-connection");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * Test validation errors on create
     */
    public function test_create_integration_validation_errors(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/integrations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id', 'name', 'type', 'configuration']);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access_denied(): void
    {
        $integration = Integration::factory()
            ->for($this->workspace)
            ->create();

        $response = $this->getJson("/api/integrations/{$integration->id}");

        $response->assertStatus(401);
    }

    /**
     * Test get integrations by workspace
     */
    public function test_get_integrations_by_workspace(): void
    {
        $otherWorkspace = Workspace::factory()->create();
        
        Integration::factory()
            ->count(2)
            ->for($this->workspace)
            ->create();
            
        Integration::factory()
            ->for($otherWorkspace)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/integrations/workspace/{$this->workspace->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
