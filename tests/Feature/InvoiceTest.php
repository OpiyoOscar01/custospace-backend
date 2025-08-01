<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class InvoiceTest
 * 
 * Feature tests for invoice API endpoints
 * 
 * @package Tests\Feature
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
    }

    /**
     * Test user can list invoices.
     */
    public function test_user_can_list_invoices(): void
    {
        Invoice::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'stripe_id',
                        'number',
                        'amount',
                        'currency',
                        'status',
                        'is_paid',
                        'is_overdue',
                        'is_open',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test user can create invoice.
     */
    public function test_user_can_create_invoice(): void
    {
        $invoiceData = [
            'workspace_id' => $this->workspace->id,
            'stripe_id' => 'in_test123',
            'number' => 'INV-001',
            'amount' => 100.50,
            'currency' => 'USD',
            'status' => Invoice::STATUS_DRAFT,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoices', $invoiceData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'workspace_id' => $this->workspace->id,
                'stripe_id' => 'in_test123',
                'number' => 'INV-001',
                'amount' => '100.50',
            ]);

        $this->assertDatabaseHas('invoices', [
            'workspace_id' => $this->workspace->id,
            'stripe_id' => 'in_test123',
            'number' => 'INV-001',
        ]);
    }

    /**
     * Test user can view invoice.
     */
    public function test_user_can_view_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $invoice->id,
                'workspace_id' => $this->workspace->id,
            ]);
    }

    /**
     * Test user can update invoice.
     */
    public function test_user_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'workspace_id' => $this->workspace->id,
            'amount' => 100.00,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $updateData = [
            'amount' => 150.75,
            'status' => Invoice::STATUS_OPEN,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'amount' => '150.75',
                'status' => Invoice::STATUS_OPEN,
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount' => 150.75,
            'status' => Invoice::STATUS_OPEN,
        ]);
    }

    /**
     * Test user can delete invoice.
     */
    public function test_user_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * Test user cannot delete paid invoice.
     */
    public function test_user_cannot_delete_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Cannot delete paid invoices.'
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * Test user can mark invoice as paid.
     */
    public function test_user_can_mark_invoice_as_paid(): void
    {
        $invoice = Invoice::factory()->open()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/invoices/{$invoice->id}/mark-as-paid");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => Invoice::STATUS_PAID,
                'is_paid' => true,
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    /**
     * Test user can mark invoice as void.
     */
    public function test_user_can_mark_invoice_as_void(): void
    {
        $invoice = Invoice::factory()->open()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/invoices/{$invoice->id}/mark-as-void");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => Invoice::STATUS_VOID,
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_VOID,
        ]);
    }

    /**
     * Test user can send invoice.
     */
    public function test_user_can_send_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'workspace_id' => $this->workspace->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/invoices/{$invoice->id}/send");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => Invoice::STATUS_OPEN,
                'is_open' => true,
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_OPEN,
        ]);
    }

    /**
     * Test user can get invoice statistics.
     */
    public function test_user_can_get_invoice_statistics(): void
    {
        // Create various invoices for the workspace
        Invoice::factory()->paid()->create(['workspace_id' => $this->workspace->id, 'amount' => 100]);
        Invoice::factory()->open()->create(['workspace_id' => $this->workspace->id, 'amount' => 200]);
        Invoice::factory()->overdue()->create(['workspace_id' => $this->workspace->id, 'amount' => 150]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/invoices/stats?workspace_id={$this->workspace->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'paid',
                    'open',
                    'overdue',
                    'total_amount',
                    'total_paid',
                ]
            ]);
    }

    /**
     * Test validation errors for invoice creation.
     */
    public function test_invoice_creation_validation_errors(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'workspace_id',
                'stripe_id',
                'number',
                'amount',
                'currency',
                'status'
            ]);
    }

    /**
     * Test unique stripe_id validation.
     */
    public function test_stripe_id_must_be_unique(): void
    {
        Invoice::factory()->create(['stripe_id' => 'in_duplicate']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/invoices', [
                'workspace_id' => $this->workspace->id,
                'stripe_id' => 'in_duplicate',
                'number' => 'INV-002',
                'amount' => 100,
                'currency' => 'USD',
                'status' => Invoice::STATUS_DRAFT,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stripe_id']);
    }

    /**
     * Test unauthorized access to invoices.
     */
    public function test_unauthorized_access_to_invoices(): void
    {
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(401);
    }
}
