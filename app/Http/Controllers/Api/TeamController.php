<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\Workspace;
use App\Services\TeamService;
use Illuminate\Http\Request;

use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TeamController extends Controller
{
    use AuthorizesRequests;

    protected $teamService;

    /**
     * Create a new controller instance.
     */
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
        $this->authorizeResource(Team::class, 'team');
    }

    /**
     * Display a listing of the teams for a workspace.
     */
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        
        $teams = $this->teamService->getTeamsByWorkspace($workspace, $request->all());
        return TeamResource::collection($teams);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(CreateTeamRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);
        
        $data = $request->validated();
        $data['workspace_id'] = $workspace->id;
        
        $team = $this->teamService->createTeam($data);
        return new TeamResource($team);
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        $team = $this->teamService->getTeamById($team->id);
        return new TeamResource($team);
    }

    /**
     * Update the specified team in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team = $this->teamService->updateTeam($team, $request->validated());
        return new TeamResource($team);
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        $this->teamService->deleteTeam($team);
        return response()->noContent();
    }

    /**
     * Activate a team.
     * 
     * @param Team $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Team $team)
    {
        $this->authorize('update', $team);
        $team = $this->teamService->activateTeam($team);
        return new TeamResource($team);
    }

    /**
     * Deactivate a team.
     * 
     * @param Team $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate(Team $team)
    {
        $this->authorize('update', $team);
        $team = $this->teamService->deactivateTeam($team);
        return new TeamResource($team);
    }

    /**
     * Assign a user to the team.
     * 
     * @param Request $request
     * @param Team $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignUser(Request $request, Team $team)
    {
        $this->authorize('update', $team);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member,viewer',
        ]);

        $team = $this->teamService->assignUser(
            $team, 
            $validated['user_id'], 
            $validated['role']
        );

        return new TeamResource($team);
    }
}
