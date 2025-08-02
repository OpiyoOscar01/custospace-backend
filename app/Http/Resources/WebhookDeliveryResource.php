<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * WebhookDelivery API Resource
 * 
 * Transforms webhook delivery data for API responses
 */
class WebhookDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'webhook_id' => $this->webhook_id,
            'event' => $this->event,
            'payload' => $this->payload,
            'response_code' => $this->response_code,
            'response_body' => $this->when(
                $request->user()?->can('viewResponse', $this->resource),
                $this->response_body
            ),
            'status' => $this->status,
            'attempts' => $this->attempts,
            'next_attempt_at' => $this->next_attempt_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'webhook' => $this->whenLoaded('webhook', function () {
                return [
                    'id' => $this->webhook->id,
                    'name' => $this->webhook->name,
                    'url' => $this->webhook->url,
                ];
            }),
            
            // Computed attributes
            'is_pending' => $this->isPending(),
            'is_delivered' => $this->isDelivered(),
            'is_failed' => $this->isFailed(),
            'can_retry' => $this->isFailed() && $this->attempts < 5,
            
            // Actions (if user has permissions)
            'actions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                return [
                    'can_update' => $user->can('update', $this->resource),
                    'can_delete' => $user->can('delete', $this->resource),
                    'can_retry' => $user->can('retry', $this->resource),
                ];
            }),
        ];
    }
}
