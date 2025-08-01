<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class InvitationTest
 * 
 * Feature tests for invitation API endpoints
 * 
 * @package Tests\Feature
 */
class InvitationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
    }

    /**
     * Test user can list invitations
     */
    public function test_user_can_list_invitations(): void
    {
        // Create test invitations
        $invitations = Invitation::factory()
            ->count(3)
            ->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/invitations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'email',
                        'role',
                        'status',
                        'expires_at',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create invitation
     */
    public function test_user_can_create_invitation(): void
    {
        $invitationData = [
            'workspace_id' => $this->workspace->id,
            'email' => $this->faker->email(),
            'role' => 'member',
            'expires_at' => now()->addDays(7)->toISOString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/invitations', $invitationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workspace_id',
                    'email',
                    'role',
                    'status',
                    'invited_by',
                ]
            ]);

        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $invitationData['workspace_id'],
            'email' => $invitationData['email'],
            'role' => $invitationData['role'],
            'invited_by_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can view invitation
     */
    public function test_user_can_view_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'workspace_id' => $this->workspace->id,
            'invited_by_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invitations/{$invitation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                ]
            ]);
    }

    /**
     * Test user can update invitation
     */
    public function test_user_can_update_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'workspace_id' => $this->workspace->id,
            'invited_by_id' => $this->user->id,
            'role' => 'member',
        ]);

        $updateData = [
            'role' => 'admin',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/invitations/{$invitation->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $invitation->id,
                    'role' => 'admin',
                ]
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'role' => 'admin',
        ]);
    }

    /**
     * Test user can delete invitation
     */
    public function test_user_can_delete_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'workspace_id' => $this->workspace->id,
            'invited_by_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/invitations/{$invitation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation deleted successfully'
            ]);

        $this->assertDatabaseMissing('invitations', [
            'id' => $invitation->id,
        ]);
    }

    /**
     * Test user can accept invitation
     */
    public function test_user_can_accept_invitation(): void
    {
        $invitedUser = User::factory()->create();
        $invitation = Invitation::factory()->pending()->create([
            'workspace_id' => $this->workspace->id,
            'email' => $invitedUser->email,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($invitedUser)
            ->patchJson("/api/invitations/{$invitation->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation accepted successfully'
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    /**
     * Test user can decline invitation
     */
    public function test_user_can_decline_invitation(): void
    {
        $invitedUser = User::factory()->create();
        $invitation = Invitation::factory()->pending()->create([
            'workspace_id' => $this->workspace->id,
            'email' => $invitedUser->email,
        ]);

        $response = $this->actingAs($invitedUser)
            ->patchJson("/api/invitations/{$invitation->id}/decline");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation declined successfully'
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'declined',
        ]);
    }

    /**
     * Test user can resend invitation
     */
    public function test_user_can_resend_invitation(): void
    {
        $invitation = Invitation::factory()->pending()->create([
            'workspace_id' => $this->workspace->id,
            'invited_by_id' => $this->user->id,
        ]);

        $originalToken = $invitation->token;

        $response = $this->actingAs($this->user)
            ->patchJson("/api/invitations/{$invitation->id}/resend");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation resent successfully'
            ]);

        $invitation->refresh();
        $this->assertNotEquals($originalToken, $invitation->token);
    }

    /**
     * Test bulk delete invitations
     */
    public function test_user_can_bulk_delete_invitations(): void
    {
        $invitations = Invitation::factory()
            ->count(3)
            ->create([
                'workspace_id' => $this->workspace->id,
                'invited_by_id' => $this->user->id,
            ]);

        $invitationIds = $invitations->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/invitations/bulk', [
                'invitation_ids' => $invitationIds
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '3 invitations deleted successfully'
            ]);

        foreach ($invitationIds as $id) {
            $this->assertDatabaseMissing('invitations', ['id' => $id]);
        }
    }

    /**
     * Test validation errors
     */
    public function test_invitation_creation_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invitations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id', 'email', 'role']);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access_denied(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->getJson("/api/invitations/{$invitation->id}");

        $response->assertStatus(401);
    }

    /**
     * Test expired invitation cannot be accepted
     */
    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $invitedUser = User::factory()->create();
        $invitation = Invitation::factory()->expired()->create([
            'email' => $invitedUser->email,
        ]);

        $response = $this->actingAs($invitedUser)
            ->patchJson("/api/invitations/{$invitation->id}/accept");

        $response->assertStatus(422);
    }
}