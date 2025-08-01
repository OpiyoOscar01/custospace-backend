<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


/**
 * Class InvoiceController
 * 
 * Handles HTTP requests for invoice operations
 * 
 * @package App\Http\Controllers\Api
 */
class InvoiceController extends Controller
{
    use AuthorizesRequests;
    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * InvoiceController constructor.
     */
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    /**
     * Display a listing of invoices.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $invoices = $this->invoiceService->getAllPaginated($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created invoice.
     * 
     * @param CreateInvoiceRequest $request
     * @return InvoiceResource
     */
    public function store(CreateInvoiceRequest $request): InvoiceResource
    {
        $invoice = $this->invoiceService->create($request->validated());

        return new InvoiceResource($invoice);
    }

    /**
     * Display the specified invoice.
     * 
     * @param Invoice $invoice
     * @return InvoiceResource
     */
    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['workspace']));
    }

    /**
     * Update the specified invoice.
     * 
     * @param UpdateInvoiceRequest $request
     * @param Invoice $invoice
     * @return InvoiceResource
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->invoiceService->update($invoice, $request->validated());

        return new InvoiceResource($invoice->fresh(['workspace']));
    }

    /**
     * Remove the specified invoice.
     * 
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            $this->invoiceService->delete($invoice);
            
            return response()->json([
                'message' => 'Invoice deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Mark invoice as paid.
     * 
     * @param Invoice $invoice
     * @return InvoiceResource
     */
    public function markAsPaid(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);
        
        $this->invoiceService->markAsPaid($invoice);

        return new InvoiceResource($invoice->fresh(['workspace']));
    }

    /**
     * Mark invoice as void.
     * 
     * @param Invoice $invoice
     * @return InvoiceResource
     */
    public function markAsVoid(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);
        
        $this->invoiceService->markAsVoid($invoice);

        return new InvoiceResource($invoice->fresh(['workspace']));
    }

    /**
     * Mark invoice as uncollectible.
     * 
     * @param Invoice $invoice
     * @return InvoiceResource
     */
    public function markAsUncollectible(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);
        
        $this->invoiceService->markAsUncollectible($invoice);

        return new InvoiceResource($invoice->fresh(['workspace']));
    }

    /**
     * Send invoice (mark as open).
     * 
     * @param Invoice $invoice
     * @return InvoiceResource
     */
    public function send(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);
        
        $this->invoiceService->send($invoice);

        return new InvoiceResource($invoice->fresh(['workspace']));
    }

    /**
     * Get invoice statistics for workspace.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id']
        ]);

        $stats = $this->invoiceService->getStatsByWorkspace($request->workspace_id);

        return response()->json([
            'data' => $stats
        ]);
    }
}
