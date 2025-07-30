<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for Media API endpoints.
 */
class MediaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Assuming user belongs to workspace
        $this->user->workspaces()->attach($this->workspace->id);
        
        Storage::fake('public');
    }

    /**
     * Test user can list media.
     */
    public function test_user_can_list_media(): void
    {
        // Create test media
        Media::factory(3)->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id
        ]);
        Media::factory(2)->create(); // Other workspace media

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/media');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'user_id',
                        'name',
                        'original_name',
                        'url',
                        'mime_type',
                        'size',
                        'human_size',
                        'collection',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create media.
     */
    public function test_user_can_create_media(): void
    {
        $mediaData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'test-media.jpg',
            'original_name' => 'test-media.jpg',
            'path' => 'media/test-media.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'collection' => 'avatars',
            'metadata' => ['width' => 800, 'height' => 600]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media', $mediaData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workspace_id',
                    'user_id',
                    'name',
                    'original_name',
                    'url',
                    'mime_type',
                    'size',
                    'collection',
                    'metadata'
                ]
            ]);

        $this->assertDatabaseHas('media', [
            'name' => 'test-media.jpg',
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'collection' => 'avatars'
        ]);
    }

    /**
     * Test user can view media.
     */
    public function test_user_can_view_media(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workspace_id',
                    'user_id',
                    'name',
                    'original_name',
                    'url',
                    'mime_type',
                    'size',
                    'human_size',
                    'collection',
                    'metadata',
                    'is_image',
                    'is_video',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test user can update media.
     */
    public function test_user_can_update_media(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id
        ]);

        $updateData = [
            'name' => 'updated-media.jpg',
            'collection' => 'banners',
            'metadata' => ['width' => 1200, 'height' => 800]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/media/{$media->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'updated-media.jpg')
            ->assertJsonPath('data.collection', 'banners');

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'name' => 'updated-media.jpg',
            'collection' => 'banners'
        ]);
    }

    /**
     * Test user can delete media.
     */
    public function test_user_can_delete_media(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Media deleted successfully.']);

        $this->assertDatabaseMissing('media', [
            'id' => $media->id
        ]);
    }

    /**
     * Test user can move media to different collection.
     */
    public function test_user_can_move_media_to_different_collection(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'collection' => 'avatars'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/media/{$media->id}/collection", [
                'collection' => 'banners'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'collection' => 'banners'
        ]);
    }

    /**
     * Test user can update media metadata.
     */
    public function test_user_can_update_media_metadata(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'metadata' => ['width' => 800, 'height' => 600]
        ]);

        $newMetadata = ['width' => 1200, 'height' => 800, 'description' => 'Updated media'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/media/{$media->id}/metadata", [
                'metadata' => $newMetadata
            ]);

        $response->assertStatus(200);

        $media->refresh();
        $this->assertEquals($newMetadata['width'], $media->metadata['width']);
        $this->assertEquals($newMetadata['description'], $media->metadata['description']);
    }

    /**
     * Test user can duplicate media.
     */
    public function test_user_can_duplicate_media(): void
    {
        $originalMedia = Media::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'original-media.jpg'
        ]);

        // Mock file copy operation
        Storage::fake('public');
        Storage::disk('public')->put($originalMedia->path, 'fake content');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/media/{$originalMedia->id}/duplicate", [
                'name' => 'duplicated-media.jpg',
                'collection' => 'duplicates'
            ]);

        $response->assertStatus(201);

        // Check that duplicate exists in database
        $this->assertDatabaseHas('media', [
            'name' => 'duplicated-media.jpg',
            'collection' => 'duplicates',
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id
        ]);

        // Original should still exist
        $this->assertDatabaseHas('media', [
            'id' => $originalMedia->id,
            'name' => 'original-media.jpg'
        ]);
    }

    /**
     * Test unauthorized user cannot access media from different workspace.
     */
    public function test_unauthorized_user_cannot_access_media_from_different_workspace(): void
    {
        $otherWorkspace = Workspace::factory()->create();
        $media = Media::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}");

        $response->assertStatus(403);
    }

    /**
     * Test validation fails with invalid data.
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        $invalidData = [
            'workspace_id' => 'invalid', // Should be integer
            'name' => '', // Required field
            'size' => 'invalid' // Should be integer
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id', 'name', 'size']);
    }
}
