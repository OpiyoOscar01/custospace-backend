<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class InvoiceRepository
 * 
 * Handles data access operations for invoices
 * 
 * @package App\Repositories
 */
class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * @var Invoice
     */
    protected $model;

    /**
     * InvoiceRepository constructor.
     */
    public function __construct(Invoice $model)
    {
        $this->model = $model;
    }

    /**
     * Get all invoices with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['workspace'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find invoice by ID
     */
    public function findById(int $id): ?Invoice
    {
        return $this->model->with(['workspace'])->find($id);
    }

    /**
     * Create new invoice
     */
    public function create(array $data): Invoice
    {
        return $this->model->create($data);
    }

    /**
     * Update invoice
     */
    public function update(Invoice $invoice, array $data): bool
    {
        return $invoice->update($data);
    }

    /**
     * Delete invoice
     */
    public function delete(Invoice $invoice): bool
    {
        return $invoice->delete();
    }

    /**
     * Find invoice by Stripe ID
     */
    public function findByStripeId(string $stripeId): ?Invoice
    {
        return $this->model->where('stripe_id', $stripeId)->first();
    }

    /**
     * Get invoices by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)
            ->with(['workspace'])
            ->get();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(): Collection
    {
        return $this->model->overdue()
            ->with(['workspace'])
            ->get();
    }

    /**
     * Get paid invoices for workspace
     */
    public function getPaidByWorkspace(int $workspaceId): Collection
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->paid()
            ->get();
    }
}
