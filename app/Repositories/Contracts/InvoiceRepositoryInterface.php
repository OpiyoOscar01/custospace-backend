<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface InvoiceRepositoryInterface
 * 
 * Defines contract for invoice data access operations
 * 
 * @package App\Repositories\Contracts
 */
interface InvoiceRepositoryInterface
{
    /**
     * Get all invoices with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find invoice by ID
     */
    public function findById(int $id): ?Invoice;

    /**
     * Create new invoice
     */
    public function create(array $data): Invoice;

    /**
     * Update invoice
     */
    public function update(Invoice $invoice, array $data): bool;

    /**
     * Delete invoice
     */
    public function delete(Invoice $invoice): bool;

    /**
     * Find invoice by Stripe ID
     */
    public function findByStripeId(string $stripeId): ?Invoice;

    /**
     * Get invoices by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get overdue invoices
     */
    public function getOverdue(): Collection;

    /**
     * Get paid invoices for workspace
     */
    public function getPaidByWorkspace(int $workspaceId): Collection;
}
