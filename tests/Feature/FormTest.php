<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Form Feature Tests
 * 
 * Tests all form-related API endpoints and functionality
 */
class FormTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Attach user to workspace (adjust based on your workspace membership logic)
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
    }

    /**
     * Test user can list forms
     */
    public function test_user_can_list_forms(): void
    {
        Sanctum::actingAs($this->user);

        $forms = Form::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/forms');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'workspace_id',
                            'name',
                            'slug',
                            'description',
                            'fields',
                            'settings',
                            'is_active',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ])
                ->assertJsonCount(3, 'data');
    }

    /**
     * Test user can create form
     */
    public function test_user_can_create_form(): void
    {
        Sanctum::actingAs($this->user);

        $formData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Contact Form',
            'slug' => 'test-contact-form',
            'description' => 'A test contact form',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'text',
                    'label' => 'Full Name',
                    'required' => true,
                ],
                [
                    'name' => 'email',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'required' => true,
                ],
            ],
            'settings' => [
                'allow_multiple_submissions' => false,
                'require_authentication' => false,
            ],
            'is_active' => true,
        ];

        $response = $this->postJson('/api/forms', $formData);

        $response->assertCreated()
                ->assertJsonFragment([
                    'name' => 'Test Contact Form',
                    'slug' => 'test-contact-form',
                ])
                ->assertJsonPath('data.created_by.id', $this->user->id);

        $this->assertDatabaseHas('forms', [
            'name' => 'Test Contact Form',
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can view form
     */
    public function test_user_can_view_form(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/forms/{$form->id}");

        $response->assertOk()
                ->assertJsonFragment([
                    'id' => $form->id,
                    'name' => $form->name,
                    'slug' => $form->slug,
                ]);
    }

    /**
     * Test user can update form
     */
    public function test_user_can_update_form(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/forms/{$form->id}", $updateData);

        $response->assertOk()
                ->assertJsonFragment([
                    'name' => 'Updated Form Name',
                    'description' => 'Updated description',
                ]);

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
        ]);
    }

    /**
     * Test user can delete form
     */
    public function test_user_can_delete_form(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/forms/{$form->id}");

        $response->assertOk()
                ->assertJsonFragment([
                    'message' => 'Form deleted successfully'
                ]);

        $this->assertDatabaseMissing('forms', [
            'id' => $form->id,
        ]);
    }

    /**
     * Test user can activate form
     */
    public function test_user_can_activate_form(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->inactive()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/forms/{$form->id}/activate");

        $response->assertOk()
                ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test user can deactivate form
     */
    public function test_user_can_deactivate_form(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/forms/{$form->id}/deactivate");

        $response->assertOk()
                ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test user can duplicate form
     */
    public function test_user_can_duplicate_form(): void
    {
        Sanctum::actingAs($this->user);

        $originalForm = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
            'name' => 'Original Form',
        ]);

        $response = $this->postJson("/api/forms/{$originalForm->id}/duplicate", [
            'name' => 'Duplicated Form'
        ]);

        $response->assertCreated()
                ->assertJsonFragment([
                    'name' => 'Duplicated Form',
                ])
                ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('forms', [
            'name' => 'Duplicated Form',
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test form validation
     */
    public function test_form_creation_validation(): void
    {
        Sanctum::actingAs($this->user);

        // Test missing required fields
        $response = $this->postJson('/api/forms', []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'workspace_id',
                    'name',
                    'slug',
                    'fields',
                ]);

        // Test invalid field data
        $response = $this->postJson('/api/forms', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Form',
            'slug' => 'test-form',
            'fields' => [
                [
                    'name' => 'invalid_field',
                    // Missing required field properties
                ]
            ],
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'fields.0.type',
                    'fields.0.label',
                ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access_denied(): void
    {
        $form = Form::factory()->create();

        // Test without authentication
        $response = $this->getJson('/api/forms');
        $response->assertUnauthorized();

        // Test with wrong user
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/forms/{$form->id}");
        $response->assertForbidden();
    }

    /**
     * Test form filtering
     */
    public function test_form_filtering(): void
    {
        Sanctum::actingAs($this->user);

        $activeForms = Form::factory()->count(2)->active()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $inactiveForms = Form::factory()->count(3)->inactive()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        // Test filtering by active status
        $response = $this->getJson('/api/forms?is_active=1');
        $response->assertOk()->assertJsonCount(2, 'data');

        $response = $this->getJson('/api/forms?is_active=0');
        $response->assertOk()->assertJsonCount(3, 'data');

        // Test filtering by workspace
        $response = $this->getJson("/api/forms?workspace_id={$this->workspace->id}");
        $response->assertOk()->assertJsonCount(5, 'data');
    }

    /**
     * Test form analytics
     */
    public function test_form_analytics(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/forms/{$form->id}/analytics");

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'total_responses',
                        'completion_rate',
                        'field_analytics',
                    ]
                ]);
    }
}
