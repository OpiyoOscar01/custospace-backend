<?php

namespace App\Services;

use App\Models\Form;
use App\Repositories\Contracts\FormRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Form Service
 * 
 * Handles business logic for form operations
 */
class FormService
{
    public function __construct(
        private FormRepositoryInterface $formRepository
    ) {}

    /**
     * Get all forms with optional filters
     */
    public function getAllForms(array $filters = []): Collection
    {
        return $this->formRepository->all($filters);
    }

    /**
     * Get paginated forms
     */
    public function getPaginatedForms(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->formRepository->paginate($filters, $perPage);
    }

    /**
     * Find form by ID
     */
    public function findForm(int $id): ?Form
    {
        return $this->formRepository->find($id);
    }

    /**
     * Find form by slug within workspace
     */
    public function findFormBySlug(int $workspaceId, string $slug): ?Form
    {
        return $this->formRepository->findBySlug($workspaceId, $slug);
    }

    /**
     * Create a new form
     */
    public function createForm(array $data): Form
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $data['workspace_id']);
        }

        // Validate and clean form fields
        $data['fields'] = $this->validateAndCleanFields($data['fields']);

        // Set default settings if not provided
        if (!isset($data['settings'])) {
            $data['settings'] = $this->getDefaultSettings();
        }

        return $this->formRepository->create($data);
    }

    /**
     * Update an existing form
     */
    public function updateForm(Form $form, array $data): Form
    {
        // Generate new slug if name changed
        if (isset($data['name']) && $data['name'] !== $form->name && !isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $form->workspace_id, $form->id);
        }

        // Validate and clean form fields if provided
        if (isset($data['fields'])) {
            $data['fields'] = $this->validateAndCleanFields($data['fields']);
        }

        return $this->formRepository->update($form, $data);
    }

    /**
     * Delete a form
     */
    public function deleteForm(Form $form): bool
    {
        return $this->formRepository->delete($form);
    }

    /**
     * Get forms for a workspace
     */
    public function getWorkspaceForms(int $workspaceId, array $filters = []): Collection
    {
        return $this->formRepository->getByWorkspace($workspaceId, $filters);
    }

    /**
     * Activate a form
     */
    public function activateForm(Form $form): Form
    {
        return $this->formRepository->activate($form);
    }

    /**
     * Deactivate a form
     */
    public function deactivateForm(Form $form): Form
    {
        return $this->formRepository->deactivate($form);
    }

    /**
     * Duplicate a form
     */
    public function duplicateForm(Form $originalForm, array $overrides = []): Form
    {
        $data = array_merge([
            'workspace_id' => $originalForm->workspace_id,
            'created_by_id' => $originalForm->created_by_id,
            'name' => $originalForm->name . ' (Copy)',
            'description' => $originalForm->description,
            'fields' => $originalForm->fields,
            'settings' => $originalForm->settings,
            'is_active' => false, // New forms start inactive
        ], $overrides);

        return $this->createForm($data);
    }

    /**
     * Generate a unique slug for the form
     */
    private function generateUniqueSlug(string $name, int $workspaceId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $workspaceId, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists in workspace
     */
    private function slugExists(string $slug, int $workspaceId, ?int $excludeId = null): bool
    {
        $query = Form::where('workspace_id', $workspaceId)->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Validate and clean form fields
     */
    private function validateAndCleanFields(array $fields): array
    {
        return array_map(function ($field) {
            return [
                'name' => Str::slug($field['name'], '_'),
                'type' => $field['type'],
                'label' => $field['label'],
                'required' => $field['required'] ?? false,
                'options' => $field['options'] ?? [],
                'placeholder' => $field['placeholder'] ?? '',
                'help_text' => $field['help_text'] ?? '',
            ];
        }, $fields);
    }

    /**
     * Get default form settings
     */
    private function getDefaultSettings(): array
    {
        return [
            'allow_multiple_submissions' => false,
            'require_authentication' => false,
            'notification_email' => null,
            'success_message' => 'Thank you for your submission!',
            'submit_button_text' => 'Submit',
        ];
    }
}
