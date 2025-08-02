<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Backup Resource
 * 
 * Transforms backup data for consistent API responses
 */
class BackupResource extends JsonResource
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
            'type' => $this->type,
            'path' => $this->path,
            'disk' => $this->disk,
            'size' => $this->size,
            'size_formatted' => $this->formatBytes($this->size),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'error_message' => $this->error_message,
            'duration' => $this->getDuration(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Include workspace data when loaded
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),

            // Additional computed fields
            'is_completed' => $this->isCompleted(),
            'is_in_progress' => $this->isInProgress(),
            'has_failed' => $this->hasFailed(),
        ];
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $exponent = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $exponent), 2) . ' ' . $units[$exponent];
    }

    /**
     * Get human readable status label
     *
     * @return string
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Calculate backup duration
     *
     * @return string|null
     */
    private function getDuration(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        $duration = $this->started_at->diff($endTime);

        if ($duration->days > 0) {
            return $duration->format('%d days, %h hours, %i minutes');
        } elseif ($duration->h > 0) {
            return $duration->format('%h hours, %i minutes');
        } elseif ($duration->i > 0) {
            return $duration->format('%i minutes, %s seconds');
        } else {
            return $duration->format('%s seconds');
        }
    }
}
