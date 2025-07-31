<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Integration API Resource
 * 
 * Transforms integration model data for API responses
 */
class IntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'type' => $this->type,
            'configuration' => $this->getSanitizedConfiguration(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),
            
            // Computed attributes
            'status' => $this->is_active ? 'active' : 'inactive',
            'type_label' => $this->getTypeLabel(),
        ];
    }

    /**
     * Get sanitized configuration (remove sensitive data)
     */
    private function getSanitizedConfiguration(): array
    {
        $config = $this->configuration ?? [];
        
        // Remove sensitive fields from API response
        $sensitiveFields = ['api_key', 'token', 'secret', 'password'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($config[$field])) {
                $config[$field] = '***';
            }
        }
        
        return $config;
    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel(): string
    {
        $labels = [
            'slack' => 'Slack',
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            'jira' => 'Jira',
            'discord' => 'Discord',
            'teams' => 'Microsoft Teams',
            'bitbucket' => 'Bitbucket',
        ];

        return $labels[$this->type] ?? ucfirst($this->type);
    }
}
