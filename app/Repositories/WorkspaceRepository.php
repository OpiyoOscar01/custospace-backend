namespace App\Repositories;

<?php
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;

class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    public function all()
    {
        return Workspace::all();
    }

    public function find($id): ?Workspace
    {
        return Workspace::find($id);
    }

    public function create(array $data): Workspace
    {
        return Workspace::create($data);
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        $workspace->update($data);
        return $workspace;
    }

    public function delete(Workspace $workspace): bool
    {
        return $workspace->delete();
    }
}
