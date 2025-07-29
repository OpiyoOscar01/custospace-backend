<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TagRepository implements TagRepositoryInterface
{
    /**
     * @var Tag
     */
    protected Tag $model;

    /**
     * TagRepository constructor.
     *
     * @param Tag $model
     */
    public function __construct(Tag $model)
    {
        $this->model = $model;
    }

    /**
     * Get all tags by workspace.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getAllByWorkspace(int $workspaceId): Collection
    {
        return $this->model->where('workspace_id', $workspaceId)->get();
    }

    /**
     * Get paginated tags by workspace.
     *
     * @param int $workspaceId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedByWorkspace(int $workspaceId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find tag by ID.
     *
     * @param int $id
     * @return Tag|null
     */
    public function findById(int $id): ?Tag
    {
        return $this->model->find($id);
    }

    /**
     * Create a new tag.
     *
     * @param array $data
     * @return Tag
     */
    public function create(array $data): Tag
    {
        return $this->model->create($data);
    }

    /**
     * Update a tag.
     *
     * @param Tag $tag
     * @param array $data
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    /**
     * Delete a tag.
     *
     * @param Tag $tag
     * @return bool
     */
    public function delete(Tag $tag): bool
    {
        return $tag->delete();
    }

    /**
     * Find tags by task ID.
     *
     * @param int $taskId
     * @return Collection
     */
    public function findByTaskId(int $taskId): Collection
    {
        return $this->model->whereHas('tasks', function ($query) use ($taskId) {
            $query->where('tasks.id', $taskId);
        })->get();
    }
}
