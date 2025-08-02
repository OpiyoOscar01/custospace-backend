<?php

namespace App\Services;

use App\Models\Export;
use App\Repositories\Contracts\ExportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Export Service
 * 
 * Handles business logic for export operations
 */
class ExportService
{
    public function __construct(
        private ExportRepositoryInterface $exportRepository
    ) {}

    /**
     * Get paginated exports for workspace
     */
    public function getWorkspaceExports(int $workspaceId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->exportRepository->getPaginatedByWorkspace($workspaceId, $perPage);
    }

    /**
     * Create a new export
     */
    public function createExport(array $data): Export
    {
        // Set default values
        $data['status'] = $data['status'] ?? 'pending';
        
        // Set expiration date (default: 7 days from now)
        if (!isset($data['expires_at'])) {
            $data['expires_at'] = Carbon::now()->addDays(7);
        }

        return $this->exportRepository->create($data);
    }

    /**
     * Update export
     */
    public function updateExport(Export $export, array $data): Export
    {
        return $this->exportRepository->update($export, $data);
    }

    /**
     * Start export processing
     */
    public function startProcessing(Export $export): Export
    {
        return $this->exportRepository->update($export, [
            'status' => 'processing'
        ]);
    }

    /**
     * Complete export
     */
    public function completeExport(Export $export, string $filePath): Export
    {
        return $this->exportRepository->update($export, [
            'status' => 'completed',
            'file_path' => $filePath
        ]);
    }

    /**
     * Fail export
     */
    public function failExport(Export $export): Export
    {
        return $this->exportRepository->update($export, [
            'status' => 'failed'
        ]);
    }

    /**
     * Get export by ID
     */
    public function getExportById(int $id): ?Export
    {
        return $this->exportRepository->findById($id);
    }

    /**
     * Delete export
     */
    public function deleteExport(Export $export): bool
    {
        return $this->exportRepository->delete($export);
    }

    /**
     * Download export file
     */
    public function downloadExport(Export $export): ?string
    {
        if (!$export->isReadyForDownload()) {
            return null;
        }

        return storage_path('app/' . $export->file_path);
    }

    /**
     * Clean up expired exports
     */
    public function cleanupExpiredExports(): int
    {
        return $this->exportRepository->cleanupExpired();
    }

    /**
     * Generate export filename
     */
    public function generateExportFilename(string $entity, string $type, int $workspaceId): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $extension = $this->getFileExtension($type);
        
        return "exports/{$entity}_{$workspaceId}_{$timestamp}.{$extension}";
    }

    /**
     * Get file extension based on type
     */
    private function getFileExtension(string $type): string
    {
        return match ($type) {
            'csv' => 'csv',
            'excel' => 'xlsx',
            'json' => 'json',
            'pdf' => 'pdf',
            default => 'csv'
        };
    }
}
