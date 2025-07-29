<?php

namespace App\Services;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TagService
{
    /**
     * @var TagRepositoryInterface
     */
    protected TagRepositoryInterface $tagRepository;

    /**
     * TagService constructor.
     *
     * @param TagRepositoryInterface $tagRepository
     */
    public function __construct(TagRepositoryInterface $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Get all tags by workspace.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getAllByWorkspace(int $workspaceId): Collection
    {
        return $this->tagRepository->getAllByWorkspace($workspaceId);
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
        return $this->tagRepository->getPaginatedByWorkspace($workspaceId, $perPage);
    }

    /**
     * Find tag by ID.
     *
     * @param int $id
     * @return Tag|null
     */
    public function findById(int $id): ?Tag
    {
        return $this->tagRepository->findById($id);
    }

    /**
     * Create a new tag.
     *
     * @param array $data
     * @return Tag
     */
    public function create(array $data): Tag
    {
        return $this->tagRepository->create($data);
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
        return $this->tagRepository->update($tag, $data);
    }

    /**
     * Delete a tag.
     *
     * @param Tag $tag
     * @return bool
     */
    public function delete(Tag $tag): bool
    {
        return $this->tagRepository->delete($tag);
    }

    /**
     * Assign tag to task.
     *
     * @param Tag $tag
     * @param int $taskId
     * @return void
     */
    public function assignToTask(Tag $tag, int $taskId): void
    {
        $tag->tasks()->syncWithoutDetaching([$taskId]);
    }

    /**
     * Remove tag from task.
     *
     * @param Tag $tag
     * @param int $taskId
     * @return void
     */
    public function removeFromTask(Tag $tag, int $taskId): void
    {
        $tag->tasks()->detach($taskId);
    }

    /**
     * Find tags by task ID.
     *
     * @param int $taskId
     * @return Collection
     */
    public function findByTaskId(int $taskId): Collection
    {
        return $this->tagRepository->findByTaskId($taskId);
    }
}
