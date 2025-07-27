<?php

namespace App\Services;

use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Models\Workspace;

class WorkspaceService
{
    protected $repository;

    public function __construct(WorkspaceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAll()
    {
        return $this->repository->all();
    }

    public function getById($id): ?Workspace
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Workspace
    {
        return $this->repository->create($data);
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        return $this->repository->update($workspace, $data);
    }

    public function delete(Workspace $workspace): bool
    {
        return $this->repository->delete($workspace);
    }
}
