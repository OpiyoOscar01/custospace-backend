<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class EmailTemplateResource
 * 
 * Transforms email template data for API responses
 * 
 * @package App\Http\Resources
 */
class EmailTemplateResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'subject' => $this->subject,
            'content' => $this->content,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Computed attributes
            'is_system_template' => $this->isSystemTemplate(),
            'content_preview' => $this->when(
                strlen($this->content) > 200,
                substr(strip_tags($this->content), 0, 200) . '...'
            ),
            'word_count' => str_word_count(strip_tags($this->content)),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            // Usage statistics (if available)
            'usage_count' => $this->when(
                isset($this->usage_count),
                $this->usage_count ?? 0
            ),
            
            'last_used_at' => $this->when(
                isset($this->last_used_at),
                $this->last_used_at?->toISOString()
            ),
        ];
    }
}