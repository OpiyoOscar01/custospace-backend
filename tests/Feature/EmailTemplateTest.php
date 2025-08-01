<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class EmailTemplateTest
 * 
 * Feature tests for email template API endpoints
 * 
 * @package Tests\Feature
 */
class EmailTemplateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
    }

    /**
     * Test user can list email templates
     */
    public function test_user_can_list_email_templates(): void
    {
        // Create test templates
        EmailTemplate::factory()
            ->count(3)
            ->create(['workspace_id' => $this->workspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/email-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'name',
                        'slug',
                        'subject',
                        'content',
                        'type',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create email template
     */
    public function test_user_can_create_email_template(): void
    {
        $templateData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'content' => '<h1>Hello {{name}}</h1><p>This is a test template.</p>',
            'type' => 'custom',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/email-templates', $templateData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workspace_id',
                    'name',
                    'slug',
                    'subject',
                    'content',
                    'type',
                    'is_active',
                ]
            ]);

        $this->assertDatabaseHas('email_templates', [
            'workspace_id' => $templateData['workspace_id'],
            'name' => $templateData['name'],
            'subject' => $templateData['subject'],
            'type' => $templateData['type'],
        ]);
    }

    /**
     * Test user can view email template
     */
    public function test_user_can_view_email_template(): void
    {
        $template = EmailTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/email-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'subject' => $template->subject,
                ]
            ]);
    }

    /**
     * Test user can update email template
     */
    public function test_user_can_update_email_template(): void
    {
        $template = EmailTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/email-templates/{$template->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $template->id,
                    'name' => 'Updated Name',
                    'subject' => 'Updated Subject',
                ]
            ]);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ]);
    }

    /**
     * Test user can delete email template
     */
    public function test_user_can_delete_email_template(): void
    {
        $template = EmailTemplate::factory()->custom()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/email-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email template deleted successfully'
            ]);

        $this->assertDatabaseMissing('email_templates', [
            'id' => $template->id,
        ]);
    }

    /**
     * Test user can activate email template
     */
    public function test_user_can_activate_email_template(): void
    {
        $template = EmailTemplate::factory()->inactive()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/email-templates/{$template->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email template activated successfully'
            ]);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test user can deactivate email template
     */
    public function test_user_can_deactivate_email_template(): void
    {
        $template = EmailTemplate::factory()->active()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/email-templates/{$template->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email template deactivated successfully'
            ]);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test user can duplicate email template
     */
    public function test_user_can_duplicate_email_template(): void
    {
        $template = EmailTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Template',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/email-templates/{$template->id}/duplicate");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                ]
            ]);

        $this->assertDatabaseHas('email_templates', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Template (Copy)',
            'type' => 'custom',
        ]);
    }

    /**
     * Test user can preview email template
     */
    public function test_user_can_preview_email_template(): void
    {
        $template = EmailTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'content' => 'Hello {{name}}, welcome to {{workspace}}!',
        ]);

        $variables = [
            'name' => 'John Doe',
            'workspace' => 'Test Workspace',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/email-templates/{$template->id}/preview", [
                'variables' => $variables
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'subject' => $template->subject,
                'content' => 'Hello John Doe, welcome to Test Workspace!',
            ]);
    }

    /**
     * Test template creation with slug generation
     */
    public function test_template_slug_is_auto_generated(): void
    {
        $templateData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'My Test Template',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'type' => 'custom',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/email-templates', $templateData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('email_templates', [
            'name' => 'My Test Template',
            'slug' => 'my-test-template',
        ]);
    }

    /**
     * Test validation errors
     */
    public function test_email_template_creation_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/email-templates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'subject', 'content', 'type']);
    }

    /**
     * Test unique slug validation
     */
    public function test_unique_slug_validation(): void
    {
        EmailTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'slug' => 'test-template',
        ]);

        $templateData = [
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'type' => 'custom',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/email-templates', $templateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /**
     * Test system templates cannot be deleted
     */
    public function test_system_templates_cannot_be_deleted(): void
    {
        $template = EmailTemplate::factory()->system()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/email-templates/{$template->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
        ]);
    }

    /**
     * Test filtering by workspace
     */
    public function test_can_filter_templates_by_workspace(): void
    {
        $otherWorkspace = Workspace::factory()->create();
        
        EmailTemplate::factory()->create(['workspace_id' => $this->workspace->id]);
        EmailTemplate::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/email-templates?workspace_id={$this->workspace->id}");

        $response->assertStatus(200);
        
        $templates = $response->json('data');
        foreach ($templates as $template) {
            $this->assertEquals($this->workspace->id, $template['workspace_id']);
        }
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access_denied(): void
    {
        $template = EmailTemplate::factory()->create();

        $response = $this->getJson("/api/email-templates/{$template->id}");

        $response->assertStatus(401);
    }
}