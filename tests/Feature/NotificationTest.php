<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Notification Feature Tests
 * 
 * Tests for notification API endpoints
 */
class NotificationTest extends TestCase
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

    public function test_user_can_list_notifications(): void
    {
        Sanctum::actingAs($this->adminUser);

        Notification::factory()->count(5)->create();

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'type',
                            'title',
                            'message',
                            'data',
                            'notifiable_type',
                            'notifiable_id',
                            'is_read',
                            'read_at',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
    }

    public function test_user_can_create_notification(): void
    {
        Sanctum::actingAs($this->user);

        $notificationData = [
            'user_id' => $this->user->id,
            'type' => 'info',
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'notifiable_type' => 'App\\Models\\Task',
            'notifiable_id' => 1,
            'data' => ['key' => 'value'],
        ];

        $response = $this->postJson('/api/notifications', $notificationData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'user_id' => $this->user->id,
                    'type' => 'info',
                    'title' => 'Test Notification',
                    'is_read' => false,
                ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'info',
            'title' => 'Test Notification',
            'is_read' => false,
        ]);
    }

    public function test_user_can_view_notification(): void
    {
        Sanctum::actingAs($this->user);

        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $notification->id,
                    'user_id' => $this->user->id,
                ]);
    }

    public function test_user_can_update_notification(): void
    {
        Sanctum::actingAs($this->user);

        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'is_read' => true,
        ];

        $response = $this->putJson("/api/notifications/{$notification->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'title' => 'Updated Title',
                    'is_read' => true,
                ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'title' => 'Updated Title',
            'is_read' => true,
        ]);
    }

    public function test_user_can_delete_notification(): void
    {
        Sanctum::actingAs($this->user);

        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Notification deleted successfully'
                ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $notification = Notification::factory()->unread()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Notification marked as read'
                ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_notification_as_unread(): void
    {
        Sanctum::actingAs($this->user);

        $notification = Notification::factory()->read()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("/api/notifications/{$notification->id}/unread");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Notification marked as unread'
                ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function test_user_can_get_their_notifications(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(2)->create(); // Other users' notifications

        $response = $this->getJson('/api/notifications/user/my-notifications');

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }

    public function test_user_can_get_unread_notifications(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->count(2)->unread()->create(['user_id' => $this->user->id]);
        Notification::factory()->count(3)->read()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/notifications/user/unread');

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->count(5)->unread()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson('/api/notifications/user/mark-all-read');

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'All notifications marked as read'
                ]);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->unread()->count());
    }

    public function test_user_can_get_unread_count(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->count(3)->unread()->create(['user_id' => $this->user->id]);
        Notification::factory()->count(2)->read()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/notifications/user/unread-count');

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'unread_count' => 3
                ]);
    }

    public function test_user_cannot_access_other_users_notifications(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_invalid_notification_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/notifications', [
            'user_id' => 999, // Non-existent user
            'title' => '', // Empty title
            'message' => '', // Empty message
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'title', 'message']);
    }
}
