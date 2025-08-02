<?php

namespace App\Services;

use App\Models\Import;
use App\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Import Service
 * 
 * Handles business logic for import operations
 */
class ImportService
{
    public function __construct(
        private ImportRepositoryInterface $importRepository
    ) {}

    /**
     * Get paginated imports for workspace
     */
    public function getWorkspaceImports(int $workspaceId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->importRepository->getPaginatedByWorkspace($workspaceId, $perPage);
    }

    /**
     * Create a new import
     */
    public function createImport(array $data, ?UploadedFile $file = null): Import
    {
        // Handle file upload if provided
        if ($file) {
            $filePath = $this->storeImportFile($file);
            $data['file_path'] = $filePath;
        }

        // Set default values
        $data['status'] = $data['status'] ?? 'pending';
        $data['total_rows'] = $data['total_rows'] ?? 0;

        return $this->importRepository->create($data);
    }

    /**
     * Update import
     */
    public function updateImport(Import $import, array $data): Import
    {
        return $this->importRepository->update($import, $data);
    }

    /**
     * Start import processing
     */
    public function startProcessing(Import $import): Import
    {
        return $this->importRepository->update($import, [
            'status' => 'processing'
        ]);
    }

    /**
     * Complete import
     */
    public function completeImport(Import $import): Import
    {
        return $this->importRepository->update($import, [
            'status' => 'completed'
        ]);
    }

    /**
     * Fail import
     */
    public function failImport(Import $import, array $errors = []): Import
    {
        return $this->importRepository->update($import, [
            'status' => 'failed',
            'errors' => $errors
        ]);
    }

    /**
     * Update import progress
     */
    public function updateProgress(Import $import, int $processedRows, int $successfulRows, int $failedRows, array $errors = []): Import
    {
        return $this->importRepository->updateProgress($import, $processedRows, $successfulRows, $failedRows, $errors);
    }

    /**
     * Get import by ID
     */
    public function getImportById(int $id): ?Import
    {
        return $this->importRepository->findById($id);
    }

    /**
     * Delete import
     */
    public function deleteImport(Import $import): bool
    {
        return $this->importRepository->delete($import);
    }

    /**
     * Get in-progress imports
     */
    public function getInProgressImports(): Collection
    {
        return $this->importRepository->getInProgress();
    }

    /**
     * Store import file
     */
    private function storeImportFile(UploadedFile $file): string
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        return $file->storeAs('imports', $filename, 'local');
    }

    /**
     * Validate import file
     */
    public function validateImportFile(UploadedFile $file, string $expectedType): bool
    {
        $allowedMimes = [
            'csv' => ['text/csv', 'application/csv'],
            'excel' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'json' => ['application/json']
        ];

        $fileMime = $file->getMimeType();
        
        return in_array($fileMime, $allowedMimes[$expectedType] ?? []);
    }
}
