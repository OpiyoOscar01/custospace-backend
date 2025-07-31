<?php

namespace App\Repositories;

use App\Models\FormResponse;
use App\Models\Form;
use App\Repositories\Contracts\FormResponseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Form Response Repository Implementation
 * 
 * Handles all database operations for form responses
 */
class FormResponseRepository implements FormResponseRepositoryInterface
{
    /**
     * Get all form responses with optional filters
     */
    public function all(array $filters = []): Collection
    {
        $query = FormResponse::with(['form', 'user']);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Get paginated form responses with optional filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FormResponse::with(['form', 'user']);

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    /**
     * Find form response by ID
     */
    public function find(int $id): ?FormResponse
    {
        return FormResponse::with(['form', 'user'])->find($id);
    }

    /**
     * Find form response by ID or fail
     */
    public function findOrFail(int $id): FormResponse
    {
        return FormResponse::with(['form', 'user'])->findOrFail($id);
    }

    /**
     * Create a new form response
     */
    public function create(array $data): FormResponse
    {
        return FormResponse::create($data);
    }

    /**
     * Update an existing form response
     */
    public function update(FormResponse $formResponse, array $data): FormResponse
    {
        $formResponse->update($data);
        return $formResponse->fresh();
    }

    /**
     * Delete a form response
     */
    public function delete(FormResponse $formResponse): bool
    {
        return $formResponse->delete();
    }

    /**
     * Get responses for a specific form
     */
    public function getByForm(int $formId, array $filters = []): Collection
    {
        $query = FormResponse::forForm($formId)->with(['user']);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Get responses by a specific user
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = FormResponse::forUser($userId)->with(['form']);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Get response statistics for a form
     */
    public function getFormStatistics(Form $form): array
    {
        $responses = $form->responses();

        return [
            'total_responses' => $responses->count(),
            'unique_users' => $responses->whereNotNull('user_id')->distinct('user_id')->count(),
            'anonymous_responses' => $responses->whereNull('user_id')->count(),
            'latest_response' => $responses->latest()->first()?->created_at,
            'first_response' => $responses->oldest()->first()?->created_at,
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['form_id'])) {
            $query->forForm($filters['form_id']);
        }

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['has_user'])) {
            if ($filters['has_user']) {
                $query->whereNotNull('user_id');
            } else {
                $query->whereNull('user_id');
            }
        }

        return $query->orderBy('created_at', 'desc');
    }
}
