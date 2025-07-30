<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Reminder Feature Tests
 * 
 * Tests for reminder API endpoints
 */
class ReminderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create(['role' => 'admin']);
    }

    public function test_user_can_list_reminders(): void
    {
        Sanctum::actingAs($this->adminUser);

        Reminder::factory()->count(5)->create();

        $response = $this->getJson('/api/reminders');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'remindable_type',
                            'remindable_id',
                            'remind_at',
                            'type',
                            'is_sent',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
    }

    public function test_user_can_create_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $reminderData = [
            'user_id' => $this->user->id,
            'remindable_type' => 'App\\Models\\Task',
            'remindable_id' => 1,
            'remind_at' => now()->addDays(1)->toDateTimeString(),
            'type' => 'email',
        ];

        $response = $this->postJson('/api/reminders', $reminderData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'user_id' => $this->user->id,
                    'type' => 'email',
                    'is_sent' => false,
                ]);

        $this->assertDatabaseHas('reminders', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'is_sent' => false,
        ]);
    }

    public function test_user_can_view_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $reminder = Reminder::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/reminders/{$reminder->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $reminder->id,
                    'user_id' => $this->user->id,
                ]);
    }

    public function test_user_can_update_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $reminder = Reminder::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'type' => 'sms',
            'remind_at' => now()->addDays(2)->toDateTimeString(),
        ];

        $response = $this->putJson("/api/reminders/{$reminder->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'type' => 'sms',
                ]);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'type' => 'sms',
        ]);
    }

    public function test_user_can_delete_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $reminder = Reminder::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/reminders/{$reminder->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Reminder deleted successfully'
                ]);

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);
    }

    public function test_user_can_activate_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $reminder = Reminder::factory()->sent()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("/api/reminders/{$reminder->id}/activate", [
            'remind_at' => now()->addDays(1)->toDateTimeString(),
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Reminder activated successfully'
                ]);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_sent' => false,
        ]);
    }

    public function test_user_can_deactivate_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $reminder = Reminder::factory()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("/api/reminders/{$reminder->id}/deactivate");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Reminder deactivated successfully'
                ]);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_sent' => true,
        ]);
    }

    public function test_user_can_get_their_reminders(): void
    {
        Sanctum::actingAs($this->user);

        Reminder::factory()->count(3)->create(['user_id' => $this->user->id]);
        Reminder::factory()->count(2)->create(); // Other users' reminders

        $response = $this->getJson('/api/reminders/user/my-reminders');

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_process_pending_reminders(): void
    {
        Sanctum::actingAs($this->adminUser);

        Reminder::factory()->count(3)->overdue()->create();

        $response = $this->postJson('/api/reminders/process-pending');

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'processed_count' => 3,
                ]);
    }

    public function test_user_cannot_access_other_users_reminders(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/reminders/{$reminder->id}");

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_invalid_reminder_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/reminders', [
            'user_id' => 999, // Non-existent user
            'type' => 'invalid_type',
            'remind_at' => 'invalid_date',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'type', 'remind_at']);
    }
}
