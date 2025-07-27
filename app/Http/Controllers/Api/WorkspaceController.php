<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Services\WorkspaceService;

class WorkspaceController extends Controller
{
    protected $service;

    public function __construct(WorkspaceService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return WorkspaceResource::collection($this->service->getAll());
    }

    public function store(WorkspaceRequest $request)
    {
        $workspace = $this->service->create($request->validated());
        return new WorkspaceResource($workspace);
    }

    public function show($id)
    {
        $workspace = $this->service->getById($id);
        return new WorkspaceResource($workspace);
    }

    public function update(WorkspaceRequest $request, Workspace $workspace)
    {
        $workspace = $this->service->update($workspace, $request->validated());
        return new WorkspaceResource($workspace);
    }

    public function destroy(Workspace $workspace)
    {
        $this->service->delete($workspace);
        return response()->json(['message' => 'Workspace deleted']);
    }
}

