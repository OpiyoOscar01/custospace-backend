<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Setting Feature Tests
 * 
 * Tests setting API endpoints and functionality
 */
class SettingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Assume you have permissions system
        $this->user->givePermissionTo([
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
            'settings.view_values',
            'settings.manage_global',
        ]);
    }

    /**
     * Test user can list settings
     */
    public function test_user_can_list_settings(): void
    {
        // Arrange
        Setting::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/settings');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'key',
                        'value',
                        'type',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create setting
     */
    public function test_user_can_create_setting(): void
    {
        // Arrange
        $settingData = [
            'workspace_id' => $this->workspace->id,
            'key' => 'test.setting',
            'value' => 'test value',
            'type' => 'string',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings', $settingData);

        // Assert
        $response->assertCreated()
            ->assertJsonFragment([
                'key' => 'test.setting',
                'value' => 'test value',
                'type' => 'string',
            ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'test.setting',
            'value' => 'test value',
        ]);
    }

    /**
     * Test user can view setting
     */
    public function test_user_can_view_setting(): void
    {
        // Arrange
        $setting = Setting::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/settings/{$setting->id}");

        // Assert
        $response->assertOk()
            ->assertJsonFragment([
                'id' => $setting->id,
                'key' => $setting->key,
            ]);
    }

    /**
     * Test user can update setting
     */
    public function test_user_can_update_setting(): void
    {
        // Arrange
        $setting = Setting::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        
        $updateData = [
            'value' => 'updated value',
            'type' => 'string',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/settings/{$setting->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJsonFragment([
                'value' => 'updated value',
            ]);

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'value' => 'updated value',
        ]);
    }

    /**
     * Test user can delete setting
     */
    public function test_user_can_delete_setting(): void
    {
        // Arrange
        $setting = Setting::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/settings/{$setting->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Setting deleted successfully'
            ]);

        $this->assertDatabaseMissing('settings', [
            'id' => $setting->id,
        ]);
    }

    /**
     * Test user can get setting value by key
     */
    public function test_user_can_get_setting_value_by_key(): void
    {
        // Arrange
        $setting = Setting::factory()->create([
            'workspace_id' => $this->workspace->id,
            'key' => 'test.key',
            'value' => 'test value',
            'type' => 'string',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/settings/value/get?' . http_build_query([
                'key' => 'test.key',
                'workspace_id' => $this->workspace->id,
            ]));

        // Assert
        $response->assertOk()
            ->assertJson([
                'key' => 'test.key',
                'value' => 'test value',
                'workspace_id' => $this->workspace->id,
            ]);
    }

    /**
     * Test user can set setting value by key
     */
    public function test_user_can_set_setting_value_by_key(): void
    {
        // Arrange
        $data = [
            'key' => 'new.setting',
            'value' => 'new value',
            'type' => 'string',
            'workspace_id' => $this->workspace->id,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings/value/set', $data);

        // Assert
        $response->assertCreated()
            ->assertJsonFragment([
                'key' => 'new.setting',
                'value' => 'new value',
            ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'new.setting',
            'value' => 'new value',
        ]);
    }

    /**
     * Test user can get workspace settings
     */
    public function test_user_can_get_workspace_settings(): void
    {
        // Arrange
        Setting::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);
        Setting::factory()->count(2)->create(); // Other workspace settings

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/settings/workspace/settings?' . http_build_query([
                'workspace_id' => $this->workspace->id,
            ]));

        // Assert
        $response->assertOk();
        $this->assertEquals(3, count($response->json('data')));
    }

    /**
     * Test user can get global settings
     */
    public function test_user_can_get_global_settings(): void
    {
        // Arrange
        Setting::factory()->global()->count(2)->create();
        Setting::factory()->workspace()->count(3)->create(); // Workspace settings

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/settings/global/settings');

        // Assert
        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test user can bulk update settings
     */
    public function test_user_can_bulk_update_settings(): void
    {
        // Arrange
        $data = [
            'workspace_id' => $this->workspace->id,
            'settings' => [
                'setting1' => ['value' => 'value1', 'type' => 'string'],
                'setting2' => ['value' => 'value2', 'type' => 'string'],
                'setting3' => ['value' => true, 'type' => 'boolean'],
            ],
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings/bulk-update', $data);

        // Assert
        $response->assertOk();
        $this->assertEquals(3, count($response->json('data')));

        $this->assertDatabaseHas('settings', [
            'key' => 'setting1',
            'value' => 'value1',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    /**
     * Test setting validation
     */
    public function test_setting_validation(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings', []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['key', 'value']);
    }

    /**
     * Test unique key validation per workspace
     */
    public function test_unique_key_validation_per_workspace(): void
    {
        // Arrange
        Setting::factory()->create([
            'workspace_id' => $this->workspace->id,
            'key' => 'duplicate.key',
        ]);

        $data = [
            'workspace_id' => $this->workspace->id,
            'key' => 'duplicate.key',
            'value' => 'test value',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings', $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    /**
     * Test JSON type setting
     */
    public function test_json_type_setting(): void
    {
        // Arrange
        $jsonData = ['key' => 'value', 'number' => 123, 'boolean' => true];
        $settingData = [
            'workspace_id' => $this->workspace->id,
            'key' => 'json.setting',
            'value' => $jsonData,
            'type' => 'json',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings', $settingData);

        // Assert
        $response->assertCreated();
        
        $setting = Setting::where('key', 'json.setting')->first();
        $this->assertEquals($jsonData, $setting->getTypedValueAttribute());
    }

    /**
     * Test boolean type setting
     */
    public function test_boolean_type_setting(): void
    {
        // Arrange
        $settingData = [
            'workspace_id' => $this->workspace->id,
            'key' => 'boolean.setting',
            'value' => true,
            'type' => 'boolean',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings', $settingData);

        // Assert
        $response->assertCreated();
        
        $setting = Setting::where('key', 'boolean.setting')->first();
        $this->assertTrue($setting->getTypedValueAttribute());
    }

    /**
     * Test integer type setting
     */
    public function test_integer_type_setting(): void
    {
        // Arrange
        $settingData = [
            'workspace_id' => $this->workspace->id,
            'key' => 'integer.setting',
            'value' => 123,
            'type' => 'integer',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings', $settingData);

        // Assert
        $response->assertCreated();
        
        $setting = Setting::where('key', 'integer.setting')->first();
        $this->assertEquals(123, $setting->getTypedValueAttribute());
    }

    /**
     * Test export settings
     */
    public function test_user_can_export_settings(): void
    {
        // Arrange
        Setting::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/settings/export?' . http_build_query([
                'workspace_id' => $this->workspace->id,
            ]));

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'workspace_id',
                'exported_at',
            ]);
    }

    /**
     * Test import settings
     */
    public function test_user_can_import_settings(): void
    {
        // Arrange
        $data = [
            'workspace_id' => $this->workspace->id,
            'settings' => [
                'imported.setting1' => ['value' => 'value1', 'type' => 'string'],
                'imported.setting2' => ['value' => 123, 'type' => 'integer'],
            ],
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/settings/import', $data);

        // Assert
        $response->assertOk()
            ->assertJson([
                'imported_count' => 2,
            ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'imported.setting1',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_user_cannot_access_settings(): void
    {
        // Act
        $response = $this->getJson('/api/settings');

        // Assert
        $response->assertUnauthorized();
    }
}
