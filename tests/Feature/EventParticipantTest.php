<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Event Participant Feature Tests
 * 
 * Tests all event participant-related API endpoints
 */
class EventParticipantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $participant;
    protected Workspace $workspace;
    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->participant = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        $this->event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user can list event participants
     */
    public function test_user_can_list_event_participants(): void
    {
        // Create participants
        EventParticipant::factory()->count(3)->create([
            'event_id' => $this->event->id,
        ]);

        $response = $this->getJson("/api/events/{$this->event->id}/participants");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'event_id',
                            'user_id',
                            'status',
                            'status_label',
                            'user',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test user can add participant to event
     */
    public function test_user_can_add_participant_to_event(): void
    {
        $response = $this->postJson("/api/events/{$this->event->id}/participants", [
            'user_id' => $this->participant->id,
            'status' => 'pending',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'event_id',
                        'user_id',
                        'status',
                        'user'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'event_id' => $this->event->id,
                        'user_id' => $this->participant->id,
                        'status' => 'pending',
                    ]
                ]);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test user can view specific participant
     */
    public function test_user_can_view_specific_participant(): void
    {
        $participant = EventParticipant::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id,
        ]);

        $response = $this->getJson("/api/events/{$this->event->id}/participants/{$participant->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $participant->id,
                        'event_id' => $this->event->id,
                        'user_id' => $this->participant->id,
                    ]
                ]);
    }

    /**
     * Test user can update participant status
     */
    public function test_user_can_update_participant_status(): void
    {
        $participant = EventParticipant::factory()->pending()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id,
        ]);

        $response = $this->putJson("/api/events/{$this->event->id}/participants/{$participant->id}", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $participant->id,
                        'status' => 'accepted',
                    ]
                ]);

        $this->assertDatabaseHas('event_participants', [
            'id' => $participant->id,
            'status' => 'accepted',
        ]);
    }

    /**
     * Test user can remove participant from event
     */
    public function test_user_can_remove_participant_from_event(): void
    {
        $participant = EventParticipant::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id,
        ]);

        $response = $this->deleteJson("/api/events/{$this->event->id}/participants/{$participant->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Participant removed successfully'
                ]);

        $this->assertDatabaseMissing('event_participants', [
            'id' => $participant->id,
        ]);
    }

    /**
     * Test participant can accept invitation
     */
    public function test_participant_can_accept_invitation(): void
    {
        $participant = EventParticipant::factory()->pending()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id, // Current authenticated user
        ]);

        $response = $this->patchJson("/api/events/{$this->event->id}/participants/{$participant->id}/accept");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $participant->id,
                        'status' => 'accepted',
                    ]
                ]);

        $this->assertDatabaseHas('event_participants', [
            'id' => $participant->id,
            'status' => 'accepted',
        ]);
    }

    /**
     * Test participant can decline invitation
     */
    public function test_participant_can_decline_invitation(): void
    {
        $participant = EventParticipant::factory()->pending()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id, // Current authenticated user
        ]);

        $response = $this->patchJson("/api/events/{$this->event->id}/participants/{$participant->id}/decline");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $participant->id,
                        'status' => 'declined',
                    ]
                ]);

        $this->assertDatabaseHas('event_participants', [
            'id' => $participant->id,
            'status' => 'declined',
        ]);
    }

    /**
     * Test participant can mark as tentative
     */
    public function test_participant_can_mark_as_tentative(): void
    {
        $participant = EventParticipant::factory()->pending()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id, // Current authenticated user
        ]);

        $response = $this->patchJson("/api/events/{$this->event->id}/participants/{$participant->id}/tentative");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $participant->id,
                        'status' => 'tentative',
                    ]
                ]);

        $this->assertDatabaseHas('event_participants', [
            'id' => $participant->id,
            'status' => 'tentative',
        ]);
    }

    /**
     * Test user can get participants by status
     */
    public function test_user_can_get_participants_by_status(): void
    {
        // Create participants with different statuses
        EventParticipant::factory()->accepted()->count(2)->create(['event_id' => $this->event->id]);
        EventParticipant::factory()->declined()->count(1)->create(['event_id' => $this->event->id]);
        EventParticipant::factory()->pending()->count(3)->create(['event_id' => $this->event->id]);

        $response = $this->getJson("/api/events/{$this->event->id}/participants/by-status?status=accepted");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        foreach ($response->json('data') as $participant) {
            $this->assertEquals('accepted', $participant['status']);
        }
    }

    /**
     * Test user cannot add duplicate participant
     */
    public function test_user_cannot_add_duplicate_participant(): void
    {
        // Create existing participant
        EventParticipant::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id,
        ]);

        $response = $this->postJson("/api/events/{$this->event->id}/participants", [
            'user_id' => $this->participant->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test participant validation
     */
    public function test_participant_creation_validation(): void
    {
        $response = $this->postJson("/api/events/{$this->event->id}/participants", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);

        $response = $this->postJson("/api/events/{$this->event->id}/participants", [
            'user_id' => 99999, // Non-existent user
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test user cannot accept other's invitation
     */
    public function test_user_cannot_accept_others_invitation(): void
    {
        $participant = EventParticipant::factory()->pending()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id, // Different user
        ]);

        $response = $this->patchJson("/api/events/{$this->event->id}/participants/{$participant->id}/accept");

        $response->assertStatus(403);
    }

    /**
     * Test user can get their participations
     */
    public function test_user_can_get_my_participations(): void
    {
        // Create participations for current user
        EventParticipant::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create participations for other users
        EventParticipant::factory()->count(2)->create();

        $response = $this->getJson('/api/participants/my-participations');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $participation) {
            $this->assertEquals($this->user->id, $participation['user_id']);
        }
    }

    /**
     * Test participant status update validation
     */
    public function test_participant_status_update_validation(): void
    {
        $participant = EventParticipant::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->participant->id,
        ]);

        $response = $this->putJson("/api/events/{$this->event->id}/participants/{$participant->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }
}