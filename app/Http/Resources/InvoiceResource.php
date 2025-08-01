<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class InvoiceResource
 * 
 * API resource for invoice data transformation
 * 
 * @package App\Http\Resources
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'stripe_id' => $this->stripe_id,
            'number' => $this->number,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'due_date' => $this->due_date?->toISOString(),
            'line_items' => $this->line_items,
            'is_paid' => $this->isPaid(),
            'is_overdue' => $this->isOverdue(),
            'is_open' => $this->isOpen(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function() {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
        ];
    }
}
