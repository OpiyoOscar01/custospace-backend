<?php

namespace Tests\Feature;

use App\Models\CustomField;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Custom Field Feature Tests
 * 
 * Tests CRUD operations and custom actions for custom fields
 */
class CustomFieldTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user can list custom fields
     */
    public function test_user_can_list_custom_fields(): void
    {
        // Arrange
        CustomField::factory()
            ->count(5)
            ->create(['workspace_id' => $this->workspace->id]);

        // Act
        $response = $this->getJson('/api/custom-fields');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'workspace_id',
                            'name',
                            'key',
                            'type',
                            'applies_to',
                            'options',
                            'is_required',
                            'order',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /**
     * Test user can create custom field
     */
    public function test_user_can_create_custom_field(): void
    {
        // Arrange
        $customFieldData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Priority Level',
            'key' => 'priority_level',
            'type' => 'select',
            'applies_to' => 'tasks',
            'options' => ['Low', 'Medium', 'High'],
            'is_required' => true,
            'order' => 1,
        ];

        // Act
        $response = $this->postJson('/api/custom-fields', $customFieldData);

        // Assert
        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'Priority Level',
                    'key' => 'priority_level',
                    'type' => 'select',
                ]);
        
        $this->assertDatabaseHas('custom_fields', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Priority Level',
            'key' => 'priority_level',
        ]);
    }

    /**
     * Test user can view custom field
     */
    public function test_user_can_view_custom_field(): void
    {
        // Arrange
        $customField = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        // Act
        $response = $this->getJson("/api/custom-fields/{$customField->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $customField->id,
                    'name' => $customField->name,
                    'key' => $customField->key,
                ]);
    }

    /**
     * Test user can update custom field
     */
    public function test_user_can_update_custom_field(): void
    {
        // Arrange
        $customField = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);
        
        $updateData = [
            'name' => 'Updated Field Name',
            'is_required' => true,
        ];

        // Act
        $response = $this->putJson("/api/custom-fields/{$customField->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $customField->id,
                    'name' => 'Updated Field Name',
                    'is_required' => true,
                ]);
        
        $this->assertDatabaseHas('custom_fields', [
            'id' => $customField->id,
            'name' => 'Updated Field Name',
            'is_required' => true,
        ]);
    }

    /**
     * Test user can delete custom field
     */
    public function test_user_can_delete_custom_field(): void
    {
        // Arrange
        $customField = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id
        ]);

        // Act
        $response = $this->deleteJson("/api/custom-fields/{$customField->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Custom field deleted successfully'
                ]);
        
        $this->assertDatabaseMissing('custom_fields', [
            'id' => $customField->id,
        ]);
    }

    /**
     * Test user can duplicate custom field
     */
    public function test_user_can_duplicate_custom_field(): void
    {
        // Arrange
        $originalField = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Field',
            'key' => 'original_field',
        ]);

        // Act
        $response = $this->postJson("/api/custom-fields/{$originalField->id}/duplicate");

        // Assert
        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'Original Field (Copy)',
                    'workspace_id' => $this->workspace->id,
                ]);
        
        $this->assertDatabaseHas('custom_fields', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Field (Copy)',
        ]);
    }

    /**
     * Test user can update custom field order
     */
    public function test_user_can_update_custom_field_order(): void
    {
        // Arrange
        $field1 = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id,
            'order' => 1,
        ]);
        
        $field2 = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id,
            'order' => 2,
        ]);
        $field3 = CustomField::factory()->create([
            'workspace_id' => $this->workspace->id,
            'order' => 3,
        ]);

        $updateData = [
            'fields' => [
                ['id' => $field2->id, 'order' => 1],
                ['id' => $field1->id, 'order' => 2],
                ['id' => $field3->id, 'order' => 3],
            ]
        ];

        // Act
        $response = $this->putJson('/api/custom-fields/update-order', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Custom field order updated successfully'
                ]);
        
        $this->assertDatabaseHas('custom_fields', [
            'id' => $field1->id,
            'order' => 2,
        ]);
        $this->assertDatabaseHas('custom_fields', [
            'id' => $field2->id,
            'order' => 1,
        ]);
    }
}