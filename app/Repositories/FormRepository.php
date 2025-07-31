<?php

namespace App\Repositories;

use App\Models\Form;
use App\Repositories\Contracts\FormRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Form Repository Implementation
 * 
 * Handles all database operations for forms
 */
class FormRepository implements FormRepositoryInterface
{
    /**
     * Get all forms with optional filters
     */
    public function all(array $filters = []): Collection
    {
        $query = Form::with(['workspace', 'createdBy']);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Get paginated forms with optional filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Form::with(['workspace', 'createdBy', 'responses']);

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    /**
     * Find form by ID
     */
    public function find(int $id): ?Form
    {
        return Form::with(['workspace', 'createdBy', 'responses'])->find($id);
    }

    /**
     * Find form by ID or fail
     */
    public function findOrFail(int $id): Form
    {
        return Form::with(['workspace', 'createdBy', 'responses'])->findOrFail($id);
    }

    /**
     * Find form by slug within workspace
     */
    public function findBySlug(int $workspaceId, string $slug): ?Form
    {
        return Form::where('workspace_id', $workspaceId)
                   ->where('slug', $slug)
                   ->with(['workspace', 'createdBy', 'responses'])
                   ->first();
    }

    /**
     * Create a new form
     */
    public function create(array $data): Form
    {
        return Form::create($data);
    }

    /**
     * Update an existing form
     */
    public function update(Form $form, array $data): Form
    {
        $form->update($data);
        return $form->fresh();
    }

    /**
     * Delete a form
     */
    public function delete(Form $form): bool
    {
        return $form->delete();
    }

    /**
     * Get forms for a specific workspace
     */
    public function getByWorkspace(int $workspaceId, array $filters = []): Collection
    {
        $query = Form::forWorkspace($workspaceId)->with(['createdBy', 'responses']);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Get active forms
     */
    public function getActive(array $filters = []): Collection
    {
        $query = Form::active()->with(['workspace', 'createdBy', 'responses']);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Activate a form
     */
    public function activate(Form $form): Form
    {
        $form->update(['is_active' => true]);
        return $form->fresh();
    }

    /**
     * Deactivate a form
     */
    public function deactivate(Form $form): Form
    {
        $form->update(['is_active' => false]);
        return $form->fresh();
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['workspace_id'])) {
            $query->forWorkspace($filters['workspace_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['created_by_id'])) {
            $query->where('created_by_id', $filters['created_by_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc');
    }
}
