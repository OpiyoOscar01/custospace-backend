<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Repositories\Contracts\EmailTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class EmailTemplateService
 * 
 * Handles business logic for email template operations
 * 
 * @package App\Services
 */
class EmailTemplateService
{
    /**
     * EmailTemplateService constructor.
     */
    public function __construct(
        protected EmailTemplateRepositoryInterface $emailTemplateRepository
    ) {}

    /**
     * Get all email templates with filters and pagination
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllTemplates(array $filters = []): LengthAwarePaginator
    {
        return $this->emailTemplateRepository->getAllWithFilters($filters);
    }

    /**
     * Create a new email template
     * 
     * @param array $data
     * @return EmailTemplate
     */
    public function createTemplate(array $data): EmailTemplate
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $data['workspace_id'] ?? null);
            }

            return $this->emailTemplateRepository->create($data);
        });
    }

    /**
     * Update an existing email template
     * 
     * @param EmailTemplate $template
     * @param array $data
     * @return EmailTemplate
     */
    public function updateTemplate(EmailTemplate $template, array $data): EmailTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            // Generate new slug if name changed and slug not provided
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug(
                    $data['name'], 
                    $template->workspace_id,
                    $template->id
                );
            }

            return $this->emailTemplateRepository->update($template, $data);
        });
    }

    /**
     * Delete an email template
     * 
     * @param EmailTemplate $template
     * @return bool
     */
    public function deleteTemplate(EmailTemplate $template): bool
    {
        if ($template->isSystemTemplate()) {
            throw new \InvalidArgumentException('System templates cannot be deleted');
        }

        return $this->emailTemplateRepository->delete($template);
    }

    /**
     * Activate an email template
     * 
     * @param EmailTemplate $template
     * @return EmailTemplate
     */
    public function activateTemplate(EmailTemplate $template): EmailTemplate
    {
        return $this->emailTemplateRepository->update($template, ['is_active' => true]);
    }

    /**
     * Deactivate an email template
     * 
     * @param EmailTemplate $template
     * @return EmailTemplate
     */
    public function deactivateTemplate(EmailTemplate $template): EmailTemplate
    {
        return $this->emailTemplateRepository->update($template, ['is_active' => false]);
    }

    /**
     * Duplicate an email template
     * 
     * @param EmailTemplate $template
     * @return EmailTemplate
     */
    public function duplicateTemplate(EmailTemplate $template): EmailTemplate
    {
        return DB::transaction(function () use ($template) {
            $data = $template->toArray();
            
            // Remove unique fields
            unset($data['id'], $data['created_at'], $data['updated_at']);
            
            // Modify name and slug for duplicate
            $data['name'] = $data['name'] . ' (Copy)';
            $data['slug'] = $this->generateUniqueSlug($data['name'], $template->workspace_id);
            $data['type'] = 'custom'; // Duplicates are always custom

            return $this->emailTemplateRepository->create($data);
        });
    }

    /**
     * Preview compiled template
     * 
     * @param EmailTemplate $template
     * @param array $variables
     * @return string
     */
    public function previewTemplate(EmailTemplate $template, array $variables = []): string
    {
        return $template->compile($variables);
    }

    /**
     * Get active templates for workspace
     * 
     * @param int|null $workspaceId
     * @return Collection
     */
    public function getActiveTemplatesForWorkspace(?int $workspaceId = null): Collection
    {
        return $this->emailTemplateRepository->getActiveByWorkspace($workspaceId);
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
        return $this->emailTemplateRepository->findBySlug($slug, $workspaceId);
    }

    /**
     * Generate unique slug for template
     * 
     * @param string $name
     * @param int|null $workspaceId
     * @param int|null $excludeId
     * @return string
     */
    protected function generateUniqueSlug(string $name, ?int $workspaceId = null, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->emailTemplateRepository->slugExists($slug, $workspaceId, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}