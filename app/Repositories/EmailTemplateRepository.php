<?php

namespace App\Repositories;

use App\Models\EmailTemplate;
use App\Repositories\Contracts\EmailTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class EmailTemplateRepository
 * 
 * Repository implementation for email template operations
 * 
 * @package App\Repositories
 */
class EmailTemplateRepository implements EmailTemplateRepositoryInterface
{
    /**
     * Get all templates with filters and pagination
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = EmailTemplate::query()
            ->with(['workspace'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new template
     * 
     * @param array $data
     * @return EmailTemplate
     */
    public function create(array $data): EmailTemplate
    {
        return EmailTemplate::create($data);
    }

    /**
     * Update a template
     * 
     * @param EmailTemplate $template
     * @param array $data
     * @return EmailTemplate
     */
    public function update(EmailTemplate $template, array $data): EmailTemplate
    {
        $template->update($data);
        return $template->fresh();
    }

    /**
     * Delete a template
     * 
     * @param EmailTemplate $template
     * @return bool
     */
    public function delete(EmailTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Find template by slug
     * 
     * @param string $slug
     * @param int|null $workspaceId
     * @return EmailTemplate|null
     */
    public function findBySlug(string $slug, ?int $workspaceId = null): ?EmailTemplate
    {
        $query = EmailTemplate::where('slug', $slug);

        if ($workspaceId) {
            $query->byWorkspace($workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        return $query->first();
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug
     * @param int|null $workspaceId
     * @param int|null $excludeId
     * @return bool
     */
    public function slugExists(string $slug, ?int $workspaceId = null, ?int $excludeId = null): bool
    {
        $query = EmailTemplate::where('slug', $slug);

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active templates by workspace
     * 
     * @param int|null $workspaceId
     * @return Collection
     */
    public function getActiveByWorkspace(?int $workspaceId = null): Collection
    {
        $query = EmailTemplate::active();

        if ($workspaceId) {
            $query->byWorkspace($workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        return $query->get();
    }

    /**
     * Apply filters to the query
     * 
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['workspace_id'])) {
            $query->byWorkspace($filters['workspace_id']);
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
    }
}