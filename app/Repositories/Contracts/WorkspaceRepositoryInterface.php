<?php
namespace App\Repositories\Contracts;

use App\Models\Workspace;

interface WorkspaceRepositoryInterface
{
    public function all();
    public function find($id): ?Workspace;
    public function create(array $data): Workspace;
    public function update(Workspace $workspace, array $data): Workspace;
    public function delete(Workspace $workspace): bool;
}
