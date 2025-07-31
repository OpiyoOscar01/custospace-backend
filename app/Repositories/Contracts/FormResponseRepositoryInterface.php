<?php

namespace App\Repositories\Contracts;

use App\Models\FormResponse;
use App\Models\Form;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Form Response Repository Interface
 * 
 * Defines the contract for form response data access operations
 */
interface FormResponseRepositoryInterface
{
    /**
     * Get all form responses with optional filters
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated form responses with optional filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find form response by ID
     */
    public function find(int $id): ?FormResponse;

    /**
     * Find form response by ID or fail
     */
    public function findOrFail(int $id): FormResponse;

    /**
     * Create a new form response
     */
    public function create(array $data): FormResponse;

    /**
     * Update an existing form response
     */
    public function update(FormResponse $formResponse, array $data): FormResponse;

    /**
     * Delete a form response
     */
    public function delete(FormResponse $formResponse): bool;

    /**
     * Get responses for a specific form
     */
    public function getByForm(int $formId, array $filters = []): Collection;

    /**
     * Get responses by a specific user
     */
    public function getByUser(int $userId, array $filters = []): Collection;

    /**
     * Get response statistics for a form
     */
    public function getFormStatistics(Form $form): array;
}
