<?php

namespace App\Repositories\Contracts;

use App\Models\Form;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Form Repository Interface
 * 
 * Defines the contract for form data access operations
 */
interface FormRepositoryInterface
{
    /**
     * Get all forms with optional filters
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated forms with optional filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find form by ID
     */
    public function find(int $id): ?Form;

    /**
     * Find form by ID or fail
     */
    public function findOrFail(int $id): Form;

    /**
     * Find form by slug within workspace
     */
    public function findBySlug(int $workspaceId, string $slug): ?Form;

    /**
     * Create a new form
     */
    public function create(array $data): Form;

    /**
     * Update an existing form
     */
    public function update(Form $form, array $data): Form;

    /**
     * Delete a form
     */
    public function delete(Form $form): bool;

    /**
     * Get forms for a specific workspace
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection;

    /**
     * Get active forms
     */
    public function getActive(array $filters = []): Collection;

    /**
     * Activate a form
     */
    public function activate(Form $form): Form;

    /**
     * Deactivate a form
     */
    public function deactivate(Form $form): Form;
}
