<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new WorkspaceRepository();
    }

    /** @test */
    public function it_can_get_all_workspaces()
    {
        Workspace::factory()->count(3)->create();

        $workspaces = $this->repository->getAllWorkspaces();
        
        $this->assertCount(3, $workspaces);
    }

    /** @test */
    public function it_can_filter_workspaces_by_active_status()
    {
        Workspace::factory()->count(2)->create(['is_active' => true]);
        Workspace::factory()->count(1)->create(['is_active' => false]);

        $activeWorkspaces = $this->repository->getAllWorkspaces(['is_active' => true]);
        $inactiveWorkspaces = $this->repository->getAllWorkspaces(['is_active' => false]);
        
        $this->assertCount(2, $activeWorkspaces);
        $this->assertCount(1, $inactiveWorkspaces);
    }

    /** @test */
    public function it_can_get_workspace_by_id()
    {
        $workspace = Workspace::factory()->create();

        $foundWorkspace = $this->repository->getWorkspaceById($workspace->id);
        
        $this->assertEquals($workspace->id, $foundWorkspace->id);
        $this->assertEquals($workspace->name, $foundWorkspace->name);
    }

    /** @test */
    public function it_can_create_workspace()
    {
        $data = [
            'name' => 'New Workspace',
            'slug' => 'new-workspace',
            'description' => 'Test description',
            'is_active' => true,
        ];

        $workspace = $this->repository->createWorkspace($data);
        
        $this->assertEquals('New Workspace', $workspace->name);
        $this->assertEquals('new-workspace', $workspace->slug);
        $this->assertTrue($workspace->is_active);
        $this->assertDatabaseHas('workspaces', ['slug' => 'new-workspace']);
    }

    /** @test */
    public function it_can_update_workspace()
    {
        $workspace = Workspace::factory()->create();
        
        $updatedWorkspace = $this->repository->updateWorkspace($workspace, [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
        
        $this->assertEquals('Updated Name', $updatedWorkspace->name);
        $this->assertEquals('Updated description', $updatedWorkspace->description);
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_can_delete_workspace()
    {
        $workspace = Workspace::factory()->create();
        
        $result = $this->repository->deleteWorkspace($workspace);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }
}
