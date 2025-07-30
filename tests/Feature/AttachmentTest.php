<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for Attachment API endpoints.
 */
class AttachmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    /**
     * Test user can list attachments.
     */
    public function test_user_can_list_attachments(): void
    {
        // Create test attachments
        Attachment::factory(3)->create(['user_id' => $this->user->id]);
        Attachment::factory(2)->create(); // Other user's attachments

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/attachments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'attachable_type',
                        'attachable_id',
                        'name',
                        'original_name',
                        'url',
                        'mime_type',
                        'size',
                        'human_size',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create attachment.
     */
    public function test_user_can_create_attachment(): void
    {
        $attachmentData = [
            'attachable_type' => 'App\\Models\\Post',
            'attachable_id' => 1,
            'name' => 'test-file.jpg',
            'original_name' => 'test-file.jpg',
            'path' => 'attachments/test-file.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'metadata' => ['width' => 800, 'height' => 600]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attachments', $attachmentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'attachable_type',
                    'attachable_id',
                    'name',
                    'original_name',
                    'url',
                    'mime_type',
                    'size',
                    'metadata'
                ]
            ]);

        $this->assertDatabaseHas('attachments', [
            'name' => 'test-file.jpg',
            'user_id' => $this->user->id,
            'attachable_type' => 'App\\Models\\Post',
            'attachable_id' => 1
        ]);
    }

    /**
     * Test user can view attachment.
     */
    public function test_user_can_view_attachment(): void
    {
        $attachment = Attachment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'attachable_type',
                    'attachable_id',
                    'name',
                    'original_name',
                    'url',
                    'mime_type',
                    'size',
                    'human_size',
                    'metadata',
                    'is_image',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test user can update attachment.
     */
    public function test_user_can_update_attachment(): void
    {
        $attachment = Attachment::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'name' => 'updated-file.jpg',
            'metadata' => ['width' => 1200, 'height' => 800]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/attachments/{$attachment->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'updated-file.jpg');

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'name' => 'updated-file.jpg'
        ]);
    }

    /**
     * Test user can delete attachment.
     */
    public function test_user_can_delete_attachment(): void
    {
        $attachment = Attachment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Attachment deleted successfully.']);

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id
        ]);
    }

    /**
     * Test user can update attachment metadata.
     */
    public function test_user_can_update_attachment_metadata(): void
    {
        $attachment = Attachment::factory()->create([
            'user_id' => $this->user->id,
            'metadata' => ['width' => 800, 'height' => 600]
        ]);

        $newMetadata = ['width' => 1200, 'height' => 800, 'description' => 'Updated image'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/attachments/{$attachment->id}/metadata", [
                'metadata' => $newMetadata
            ]);

        $response->assertStatus(200);

        $attachment->refresh();
        $this->assertEquals($newMetadata['width'], $attachment->metadata['width']);
        $this->assertEquals($newMetadata['description'], $attachment->metadata['description']);
    }

    /**
     * Test user can move attachment to different attachable.
     */
    public function test_user_can_move_attachment_to_different_attachable(): void
    {
        $attachment = Attachment::factory()->create([
            'user_id' => $this->user->id,
            'attachable_type' => 'App\\Models\\Post',
            'attachable_id' => 1
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/attachments/{$attachment->id}/move", [
                'attachable_type' => 'App\\Models\\Article',
                'attachable_id' => 2
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'attachable_type' => 'App\\Models\\Article',
            'attachable_id' => 2
        ]);
    }

    /**
     * Test unauthorized user cannot access other user's attachment.
     */
    public function test_unauthorized_user_cannot_access_other_users_attachment(): void
    {
        $otherUser = User::factory()->create();
        $attachment = Attachment::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(403);
    }

    /**
     * Test validation fails with invalid data.
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        $invalidData = [
            'attachable_type' => '', // Required field
            'name' => '', // Required field
            'size' => 'invalid' // Should be integer
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attachments', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attachable_type', 'name', 'size']);
    }
}
