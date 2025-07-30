<?php
// tests/Feature/AuditLogTest.php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Audit Log Feature Tests
 * 
 * Tests all API endpoints and functionality for audit logs
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Authenticate user for all requests
        Sanctum::actingAs($this->user);
    }

    /**
     * Test user can list audit logs.
     */
    public function test_user_can_list_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->getJson('/api/audit-logs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'event',
                            'auditable_type',
                            'auditable_id',
                            'created_at',
                            'user',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /**
     * Test user can create audit log.
     */
    public function test_user_can_create_audit_log(): void
    {
        $auditData = [
            'event' => 'updated',
            'auditable_type' => 'App\\Models\\Project',
            'auditable_id' => 1,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ];

        $response = $this->postJson('/api/audit-logs', $auditData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'event',
                        'auditable_type',
                        'auditable_id',
                        'old_values',
                        'new_values',
                    ]
                ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'auditable_type' => 'App\\Models\\Project',
            'auditable_id' => 1,
        ]);
    }

    /**
     * Test user can view single audit log.
     */
    public function test_user_can_view_audit_log(): void
    {
        $auditLog = AuditLog::factory()->create();

        $response = $this->getJson("/api/audit-logs/{$auditLog->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'event',
                        'auditable_type',
                        'user',
                        'auditable',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $auditLog->id,
                        'event' => $auditLog->event,
                    ]
                ]);
    }

    /**
     * Test user can update audit log.
     */
    public function test_user_can_update_audit_log(): void
    {
        $auditLog = AuditLog::factory()->create();

        $updateData = [
            'event' => 'restored',
            'new_values' => ['status' => 'active'],
        ];

        $response = $this->putJson("/api/audit-logs/{$auditLog->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'event' => 'restored',
                        'new_values' => ['status' => 'active'],
                    ]
                ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'event' => 'restored',
        ]);
    }

    /**
     * Test user can delete audit log.
     */
    public function test_user_can_delete_audit_log(): void
    {
        $auditLog = AuditLog::factory()->create();

        $response = $this->deleteJson("/api/audit-logs/{$auditLog->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Audit log deleted successfully.'
                ]);

        $this->assertDatabaseMissing('audit_logs', [
            'id' => $auditLog->id,
        ]);
    }

    /**
     * Test get audit trail for model.
     */
    public function test_get_audit_trail(): void
    {
        $auditableType = 'App\\Models\\Project';
        $auditableId = 1;

        AuditLog::factory()->count(3)->create([
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
        ]);

        $response = $this->getJson('/api/audit-logs/trail', [
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'event',
                            'auditable_type',
                            'auditable_id',
                        ]
                    ]
                ]);
    }

    /**
     * Test get formatted changes.
     */
    public function test_get_formatted_changes(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'status' => 'draft'],
            'new_values' => ['name' => 'New Name', 'status' => 'published'],
        ]);

        $response = $this->getJson("/api/audit-logs/{$auditLog->id}/changes");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'name' => [
                            'old',
                            'new',
                            'field_label',
                        ],
                        'status' => [
                            'old',
                            'new',
                            'field_label',
                        ],
                    ]
                ]);
    }

    /**
     * Test get audit logs by event.
     */
    public function test_get_audit_logs_by_event(): void
    {
        AuditLog::factory()->count(3)->create(['event' => 'created']);
        AuditLog::factory()->count(2)->create(['event' => 'updated']);

        $response = $this->getJson('/api/audit-logs/event/created');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'event',
                        ]
                    ]
                ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertEquals('created', $item['event']);
        }
    }

    /**
     * Test cleanup old audit logs.
     */
    public function test_cleanup_old_audit_logs(): void
    {
        // Create old audit logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(400),
        ]);

        // Create recent audit logs
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(300),
        ]);

        $response = $this->postJson('/api/audit-logs/cleanup', [
            'retention_days' => 365,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'deleted_count',
                ]);

        // Should have deleted 3 old logs
        $this->assertEquals(2, AuditLog::count());
    }

    /**
     * Test validation errors for create audit log.
     */
    public function test_create_audit_log_validation_errors(): void
    {
        $response = $this->postJson('/api/audit-logs', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'event',
                    'auditable_type',
                    'auditable_id',
                ]);
    }

    /**
     * Test filtering audit logs.
     */
    public function test_filter_audit_logs(): void
    {
        AuditLog::factory()->create([
            'event' => 'created',
            'user_id' => $this->user->id,
        ]);

        AuditLog::factory()->create([
            'event' => 'updated',
            'user_id' => User::factory()->create()->id,
        ]);

        // Filter by event
        $response = $this->getJson('/api/audit-logs?event=created');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('created', $data[0]['event']);

        // Filter by user
        $response = $this->getJson("/api/audit-logs?user_id={$this->user->id}");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->user->id, $data[0]['user_id']);
    }
}
