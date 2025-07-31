<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Event Feature Tests
 * 
 * Tests all event-related API endpoints
 */
class EventTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user can list events
     */
    public function test_user_can_list_events(): void
    {
        // Create events in the workspace
        Event::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/events?workspace_id=' . $this->workspace->id);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'workspace_id',
                            'title',
                            'description',
                            'start_date',
                            'end_date',
                            'all_day',
                            'location',
                            'type',
                            'type_label',
                            'metadata',
                            'created_by',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'links',
                    'meta'
                ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test user can create event
     */
    public function test_user_can_create_event(): void
    {
        $eventData = [
            'workspace_id' => $this->workspace->id,
            'title' => 'Team Meeting',
            'description' => 'Weekly team sync meeting',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'all_day' => false,
            'location' => 'Conference Room A',
            'type' => 'meeting',
            'metadata' => ['priority' => 'high'],
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'workspace_id',
                        'title',
                        'description',
                        'start_date',
                        'end_date',
                        'all_day',
                        'location',
                        'type',
                        'metadata',
                        'created_by'
                    ]
                ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Team Meeting',
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can view event
     */
    public function test_user_can_view_event(): void
    {
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'workspace_id',
                        'title',
                        'description',
                        'start_date',
                        'end_date',
                        'created_by',
                        'workspace'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $event->id,
                        'title' => $event->title,
                    ]
                ]);
    }

    /**
     * Test user can update event
     */
    public function test_user_can_update_event(): void
    {
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $updateData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated description',
            'location' => 'New Location',
        ];

        $response = $this->putJson("/api/events/{$event->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $event->id,
                        'title' => 'Updated Event Title',
                        'description' => 'Updated description',
                        'location' => 'New Location',
                    ]
                ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
            'description' => 'Updated description',
            'location' => 'New Location',
        ]);
    }

    /**
     * Test user can delete event
     */
    public function test_user_can_delete_event(): void
    {
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Event deleted successfully'
                ]);

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    /**
     * Test event creation validation
     */
    public function test_event_creation_validation(): void
    {
        $response = $this->postJson('/api/events', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'workspace_id',
                    'title',
                    'start_date',
                    'end_date',
                    'type'
                ]);
    }

    /**
     * Test user can add participants to event
     */
    public function test_user_can_add_participants_to_event(): void
    {
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $participants = User::factory()->count(3)->create();

        $response = $this->postJson("/api/events/{$event->id}/add-participants", [
            'user_ids' => $participants->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Participants added successfully',
                    'participants_count' => 3,
                ]);

        foreach ($participants as $participant) {
            $this->assertDatabaseHas('event_participants', [
                'event_id' => $event->id,
                'user_id' => $participant->id,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Test user can cancel event
     */
    public function test_user_can_cancel_event(): void
    {
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/events/{$event->id}/cancel", [
            'reason' => 'Emergency cancellation',
        ]);

        $response->assertStatus(200)
                ->assertJsonPath('data.is_cancelled', true);

        $event->refresh();
        $this->assertTrue($event->metadata['cancelled']);
        $this->assertEquals('Emergency cancellation', $event->metadata['cancellation_reason']);
    }

    /**
     * Test user can reschedule event
     */
    public function test_user_can_reschedule_event(): void
    {
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $newStartDate = now()->addWeek()->format('Y-m-d H:i:s');
        $newEndDate = now()->addWeek()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->patchJson("/api/events/{$event->id}/reschedule", [
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
        ]);

        $response->assertStatus(200)
                ->assertJsonPath('data.is_rescheduled', true)
                ->assertJsonPath('data.start_date', $newStartDate)
                ->assertJsonPath('data.end_date', $newEndDate);

        $event->refresh();
        $this->assertTrue($event->metadata['rescheduled']);
    }

    /**
     * Test user can get calendar events
     */
    public function test_user_can_get_calendar_events(): void
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        Event::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
            'start_date' => now()->addDays(rand(1, 20)),
        ]);

        $response = $this->getJson('/api/events/calendar?' . http_build_query([
            'workspace_id' => $this->workspace->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'start_date',
                            'end_date',
                            'type',
                        ]
                    ]
                ]);
    }

    /**
     * Test user can get upcoming events
     */
    public function test_user_can_get_upcoming_events(): void
    {
        // Create past events
        Event::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
            'start_date' => now()->subDays(5),
        ]);

        // Create upcoming events
        Event::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
            'start_date' => now()->addDays(rand(1, 10)),
        ]);

        $response = $this->getJson('/api/events/upcoming?' . http_build_query([
            'workspace_id' => $this->workspace->id,
            'limit' => 5,
        ]));

        $response->assertStatus(200);
        
        $this->assertCount(3, $response->json('data')); // Only upcoming events
    }

    /**
     * Test user cannot update other user's event
     */
    public function test_user_cannot_update_other_users_event(): void
    {
        $otherUser = User::factory()->create();
        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/events/{$event->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test user can get their own events
     */
    public function test_user_can_get_my_events(): void
    {
        // Events created by user
        Event::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        // Events where user is participant
        $eventWithParticipation = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $eventWithParticipation->participants()->create([
            'user_id' => $this->user->id,
            'status' => 'accepted',
        ]);

        $response = $this->getJson('/api/events/my-events');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }
}