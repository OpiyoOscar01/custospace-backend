<?php

namespace App\Services;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class InvoiceService
 * 
 * Handles business logic for invoice operations
 * 
 * @package App\Services
 */
class InvoiceService
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * InvoiceService constructor.
     */
    public function __construct(InvoiceRepositoryInterface $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * Get all invoices with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->invoiceRepository->getAllPaginated($perPage);
    }

    /**
     * Find invoice by ID
     */
    public function findById(int $id): ?Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    /**
     * Create new invoice
     */
    public function create(array $data): Invoice
    {
        // Business logic for invoice creation
        $data['status'] = $data['status'] ?? Invoice::STATUS_DRAFT;
        
        return $this->invoiceRepository->create($data);
    }

    /**
     * Update invoice
     */
    public function update(Invoice $invoice, array $data): bool
    {
        // Business logic for invoice update
        return $this->invoiceRepository->update($invoice, $data);
    }

    /**
     * Delete invoice
     */
    public function delete(Invoice $invoice): bool
    {
        // Business logic before deletion (e.g., check if can be deleted)
        if ($invoice->isPaid()) {
            throw new \Exception('Cannot delete paid invoices.');
        }

        return $this->invoiceRepository->delete($invoice);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice): bool
    {
        return $this->invoiceRepository->update($invoice, [
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    /**
     * Mark invoice as void
     */
    public function markAsVoid(Invoice $invoice): bool
    {
        return $this->invoiceRepository->update($invoice, [
            'status' => Invoice::STATUS_VOID,
        ]);
    }

    /**
     * Mark invoice as uncollectible
     */
    public function markAsUncollectible(Invoice $invoice): bool
    {
        return $this->invoiceRepository->update($invoice, [
            'status' => Invoice::STATUS_UNCOLLECTIBLE,
        ]);
    }

    /**
     * Send invoice (mark as open)
     */
    public function send(Invoice $invoice): bool
    {
        // Business logic for sending invoice (e.g., send email, update Stripe)
        return $this->invoiceRepository->update($invoice, [
            'status' => Invoice::STATUS_OPEN,
        ]);
    }

    /**
     * Get invoices by workspace
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return $this->invoiceRepository->getByWorkspace($workspaceId);
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(): Collection
    {
        return $this->invoiceRepository->getOverdue();
    }

    /**
     * Calculate total revenue for workspace
     */
    public function getTotalRevenueByWorkspace(int $workspaceId): float
    {
        return $this->invoiceRepository->getPaidByWorkspace($workspaceId)
            ->sum('amount');
    }

    /**
     * Get invoice statistics for workspace
     */
    public function getStatsByWorkspace(int $workspaceId): array
    {
        $invoices = $this->invoiceRepository->getByWorkspace($workspaceId);
        
        return [
            'total' => $invoices->count(),
            'paid' => $invoices->where('status', Invoice::STATUS_PAID)->count(),
            'open' => $invoices->where('status', Invoice::STATUS_OPEN)->count(),
            'overdue' => $invoices->filter(fn($invoice) => $invoice->isOverdue())->count(),
            'total_amount' => $invoices->sum('amount'),
            'total_paid' => $invoices->where('status', Invoice::STATUS_PAID)->sum('amount'),
        ];
    }
}
