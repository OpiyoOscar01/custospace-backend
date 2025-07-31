<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Form Response Feature Tests
 * 
 * Tests all form response-related API endpoints and functionality
 */
class FormResponseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;
    protected Form $form;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->form = Form::factory()->simple()->active()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);
        
        // Attach user to workspace
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
    }

    /**
     * Test user can list form responses
     */
    public function test_user_can_list_form_responses(): void
    {
        Sanctum::actingAs($this->user);

        FormResponse::factory()->count(5)->create([
            'form_id' => $this->form->id,
        ]);

        $response = $this->getJson('/api/form-responses');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'form_id',
                            'user_id',
                            'data',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ])
                ->assertJsonCount(5, 'data');
    }

    /**
     * Test user can create form response
     */
    public function test_user_can_create_form_response(): void
    {
        $responseData = [
            'form_id' => $this->form->id,
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'message' => 'This is a test message',
            ],
        ];

        $response = $this->postJson('/api/form-responses', $responseData);

        $response->assertCreated()
                ->assertJsonFragment([
                    'form_id' => $this->form->id,
                ])
                ->assertJsonPath('data.data.name.value', 'John Doe')
                ->assertJsonPath('data.data.email.value', 'john@example.com');

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $this->form->id,
        ]);
    }

    /**
     * Test authenticated user can create form response
     */
    public function test_authenticated_user_can_create_form_response(): void
    {
        Sanctum::actingAs($this->user);

        $responseData = [
            'form_id' => $this->form->id,
            'data' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'message' => 'Authenticated submission',
            ],
        ];

        $response = $this->postJson('/api/form-responses', $responseData);

        $response->assertCreated()
                ->assertJsonPath('data.user_id', $this->user->id)
                ->assertJsonPath('data.is_anonymous', false);

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can view form response
     */
    public function test_user_can_view_form_response(): void
    {
        Sanctum::actingAs($this->user);

        $formResponse = FormResponse::factory()->create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/form-responses/{$formResponse->id}");

        $response->assertOk()
                ->assertJsonFragment([
                    'id' => $formResponse->id,
                    'form_id' => $this->form->id,
                    'user_id' => $this->user->id,
                ]);
    }

    /**
     * Test user can update form response
     */
    public function test_user_can_update_form_response(): void
    {
        Sanctum::actingAs($this->user);

        $formResponse = FormResponse::factory()->create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'data' => [
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ],
        ]);

        $updateData = [
            'data' => [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'message' => 'Updated message',
            ],
        ];

        $response = $this->putJson("/api/form-responses/{$formResponse->id}", $updateData);

        $response->assertOk()
                ->assertJsonPath('data.data.name.value', 'Updated Name')
                ->assertJsonPath('data.data.email.value', 'updated@example.com');
    }

    /**
     * Test user can delete form response
     */
    public function test_user_can_delete_form_response(): void
    {
        Sanctum::actingAs($this->user);

        $formResponse = FormResponse::factory()->create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/form-responses/{$formResponse->id}");

        $response->assertOk()
                ->assertJsonFragment([
                    'message' => 'Form response deleted successfully'
                ]);

        $this->assertDatabaseMissing('form_responses', [
            'id' => $formResponse->id,
        ]);
    }

    /**
     * Test form response validation
     */
    public function test_form_response_validation(): void
    {
        // Test missing required fields
        $response = $this->postJson('/api/form-responses', [
            'form_id' => $this->form->id,
            'data' => [
                'email' => 'test@example.com',
                // Missing required 'name' field
            ],
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['data.name']);

        // Test invalid email format
        $response = $this->postJson('/api/form-responses', [
            'form_id' => $this->form->id,
            'data' => [
                'name' => 'John Doe',
                'email' => 'invalid-email',
            ],
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['data.email']);
    }

    /**
     * Test form responses filtering
     */
    public function test_form_responses_filtering(): void
    {
        Sanctum::actingAs($this->user);

        // Create responses for different forms
        $otherForm = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        FormResponse::factory()->count(3)->create(['form_id' => $this->form->id]);
        FormResponse::factory()->count(2)->create(['form_id' => $otherForm->id]);

        // Test filtering by form
        $response = $this->getJson("/api/form-responses?form_id={$this->form->id}");
        $response->assertOk()->assertJsonCount(3, 'data');

        // Test filtering by user
        FormResponse::factory()->count(2)->create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/form-responses?user_id={$this->user->id}");
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    /**
     * Test getting responses for specific form
     */
    public function test_get_responses_for_form(): void
    {
        Sanctum::actingAs($this->user);

        FormResponse::factory()->count(4)->create(['form_id' => $this->form->id]);

        $response = $this->getJson("/api/form-responses/form/{$this->form->id}");

        $response->assertOk()
                ->assertJsonCount(4, 'data');
    }

    /**
     * Test getting user's own responses
     */
    public function test_get_user_responses(): void
    {
        Sanctum::actingAs($this->user);

        FormResponse::factory()->count(3)->create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
        ]);

        // Create responses by other users
        FormResponse::factory()->count(2)->create(['form_id' => $this->form->id]);

        $response = $this->getJson('/api/form-responses/my-responses');

        $response->assertOk()
                ->assertJsonCount(3, 'data');
    }

    /**
     * Test bulk delete responses
     */
    public function test_bulk_delete_responses(): void
    {
        Sanctum::actingAs($this->user);

        $responses = FormResponse::factory()->count(3)->create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
        ]);

        $responseIds = $responses->pluck('id')->toArray();

        $response = $this->deleteJson('/api/form-responses/bulk-delete', [
            'response_ids' => $responseIds,
        ]);

        $response->assertOk()
                ->assertJsonFragment([
                    'message' => 'Successfully deleted 3 form responses'
                ]);

        foreach ($responseIds as $id) {
            $this->assertDatabaseMissing('form_responses', ['id' => $id]);
        }
    }

    /**
     * Test inactive form submission blocked
     */
    public function test_inactive_form_submission_blocked(): void
    {
        $inactiveForm = Form::factory()->inactive()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
        ]);

        $responseData = [
            'form_id' => $inactiveForm->id,
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $response = $this->postJson('/api/form-responses', $responseData);

        $response->assertForbidden();
    }

    /**
     * Test multiple submissions handling
     */
    public function test_multiple_submissions_handling(): void
    {
        Sanctum::actingAs($this->user);

        // Create form that doesn't allow multiple submissions
        $restrictedForm = Form::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id,
            'settings' => [
                'allow_multiple_submissions' => false,
            ],
        ]);

        // First submission should succeed
        $responseData = [
            'form_id' => $restrictedForm->id,
            'data' => ['test' => 'data'],
        ];

        $response = $this->postJson('/api/form-responses', $responseData);
        $response->assertCreated();

        // Second submission should be blocked
        $response = $this->postJson('/api/form-responses', $responseData);
        $response->assertForbidden();
    }

    /**
     * Test anonymous vs authenticated submissions
     */
    public function test_anonymous_vs_authenticated_submissions(): void
    {
        // Anonymous submission
        $responseData = [
            'form_id' => $this->form->id,
            'data' => [
                'name' => 'Anonymous User',
                'email' => 'anon@example.com',
            ],
        ];

        $response = $this->postJson('/api/form-responses', $responseData);
        $response->assertCreated()
                ->assertJsonPath('data.user_id', null)
                ->assertJsonPath('data.is_anonymous', true);

        // Authenticated submission
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/form-responses', $responseData);
        $response->assertCreated()
                ->assertJsonPath('data.user_id', $this->user->id)
                ->assertJsonPath('data.is_anonymous', false);
    }

    /**
     * Test response privacy controls
     */
    public function test_response_privacy_controls(): void
    {
        $otherUser = User::factory()->create();
        $formResponse = FormResponse::factory()->create([
            'form_id' => $this->form->id,
            'user_id' => $otherUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
        ]);

        // Form owner should see sensitive data
        Sanctum::actingAs($this->user);
        $response = $this->getJson("/api/form-responses/{$formResponse->id}");
        $response->assertOk()
                ->assertJsonHas('data.ip_address')
                ->assertJsonHas('data.user_agent');

        // Other user should not see sensitive data
        Sanctum::actingAs($otherUser);
        $response = $this->getJson("/api/form-responses/{$formResponse->id}");
        $response->assertOk()
                ->assertJsonMissing(['data.ip_address'])
                ->assertJsonMissing(['data.user_agent']);
    }
}
