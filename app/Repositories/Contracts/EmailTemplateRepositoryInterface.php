<?php

namespace App\Repositories\Contracts;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface EmailTemplateRepositoryInterface
 * 
 * Contract for email template repository operations
 * 
 * @package App\Repositories\Contracts
 */
interface EmailTemplateRepositoryInterface
{
    /**
     * Get all templates with filters and pagination
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = []): LengthAwarePaginator;

    /**
     * Create a new template
     * 
     * @param array $data
     * @return EmailTemplate
     */
    public function create(array $data): EmailTemplate;

    /**
     * Update a template
     * 
     * @param EmailTemplate $template
     * @param array $data
     * @return EmailTemplate
     */
    public function update(EmailTemplate $template, array $data): EmailTemplate;

    /**
     * Delete a template
     * 
     * @param EmailTemplate $template
     * @return bool
     */
    public function delete(EmailTemplate $template): bool;

    /**
     * Find template by slug
     * 
     * @param string $slug
     * @param int|null $workspaceId
     * @return EmailTemplate|null
     */
    public function findBySlug(string $slug, ?int $workspaceId = null): ?EmailTemplate;

    /**
     * Check if slug exists
     * 
     * @param string $slug
     * @param int|null $workspaceId
     * @param int|null $excludeId
     * @return bool
     */
    public function slugExists(string $slug, ?int $workspaceId = null, ?int $excludeId = null): bool;

    /**
     * Get active templates by workspace
     * 
     * @param int|null $workspaceId
     * @return Collection
     */
    public function getActiveByWorkspace(?int $workspaceId = null): Collection;
}