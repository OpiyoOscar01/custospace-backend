<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Class InvoicePolicy
 * 
 * Authorization policies for invoice operations
 * 
 * @package App\Policies
 */
class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view') 
            && $user->canAccessWorkspace($invoice->workspace_id);
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update') 
            && $user->canAccessWorkspace($invoice->workspace_id)
            && !$invoice->isPaid(); // Cannot update paid invoices
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete') 
            && $user->canAccessWorkspace($invoice->workspace_id)
            && !$invoice->isPaid(); // Cannot delete paid invoices
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.restore');
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.force-delete');
    }
}
