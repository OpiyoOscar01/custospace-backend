<?php

namespace Tests\Unit\Repositories;

use App\Models\Team;
use App\Models\Workspace;
use App\Repositories\TeamRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;
    protected $workspace;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new TeamRepository();
        $this->workspace = Workspace::factory()->create();
    }

    /** @test */
    public function it_can_get_teams_by_workspace()
    {
        Team::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);
        Team::factory()->count(2)->create(); // Other workspace teams
        
        $teams = $this->repository->getTeamsByWorkspace($this->workspace->id);
        
        $this->assertCount(3, $teams);
        $this->assertEquals($this->workspace->id, $teams[0]->workspace_id);
    }

    /** @test */
    public function it_can_filter_teams_by_active_status()
    {
        Team::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'is_active' => true
        ]);
        
        Team::factory()->count(1)->create([
            'workspace_id' => $this->workspace->id,
            'is_active' => false
        ]);

        $activeTeams = $this->repository->getTeamsByWorkspace($this->workspace->id, ['is_active' => true]);
        $inactiveTeams = $this->repository->getTeamsByWorkspace($this->workspace->id, ['is_active' => false]);
        
        $this->assertCount(2, $activeTeams);
        $this->assertCount(1, $inactiveTeams);
    }

    /** @test */
    public function it_can_get_team_by_id()
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);

        $foundTeam = $this->repository->getTeamById($team->id);
        
        $this->assertEquals($team->id, $foundTeam->id);
        $this->assertEquals($team->name, $foundTeam->name);
    }

    /** @test */
    public function it_can_create_team()
    {
        $data = [
            'workspace_id' => $this->workspace->id,
            'name' => 'New Team',
            'slug' => 'new-team',
            'description' => 'Test description',
            'is_active' => true,
        ];

        $team = $this->repository->createTeam($data);
        
        $this->assertEquals('New Team', $team->name);
        $this->assertEquals('new-team', $team->slug);
        $this->assertEquals($this->workspace->id, $team->workspace_id);
        $this->assertTrue($team->is_active);
        $this->assertDatabaseHas('teams', ['slug' => 'new-team']);
    }

    /** @test */
    public function it_can_update_team()
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);
        
        $updatedTeam = $this->repository->updateTeam($team, [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
        
        $this->assertEquals('Updated Name', $updatedTeam->name);
        $this->assertEquals('Updated description', $updatedTeam->description);
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_can_delete_team()
    {
        $team = Team::factory()->create(['workspace_id' => $this->workspace->id]);
        
        $result = $this->repository->deleteTeam($team);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }
}
