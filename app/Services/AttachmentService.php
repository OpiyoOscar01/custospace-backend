<?php

namespace App\Services;

use App\Models\Attachment;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * Service class for handling attachment business logic.
 */
class AttachmentService
{
    /**
     * Constructor to inject repository dependency.
     */
    public function __construct(
        private AttachmentRepositoryInterface $attachmentRepository
    ) {}

    /**
     * Get all attachments with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->attachmentRepository->getAllPaginated($perPage);
    }

    /**
     * Find attachment by ID.
     */
    public function findById(int $id): ?Attachment
    {
        return $this->attachmentRepository->findById($id);
    }

    /**
     * Create a new attachment.
     */
    public function create(array $data): Attachment
    {
        // Add user_id from authenticated user
       $data['user_id'] = \Auth::id();

        return $this->attachmentRepository->create($data);
    }

    /**
     * Update an existing attachment.
     */
    public function update(Attachment $attachment, array $data): bool
    {
        return $this->attachmentRepository->update($attachment, $data);
    }

    /**
     * Delete an attachment.
     */
    public function delete(Attachment $attachment): bool
    {
        return $this->attachmentRepository->delete($attachment);
    }

    /**
     * Download attachment file.
     */
    public function download(Attachment $attachment)
    {
        $filePath = $attachment->path;
        $disk = $attachment->disk;

        if (!Storage::disk($disk)->exists($filePath)) {
            throw new \Exception('File not found.');
        }

        return Storage::disk($disk)->download($filePath, $attachment->original_name);
    }

    /**
     * Update attachment metadata.
     */
    public function updateMetadata(Attachment $attachment, array $metadata): bool
    {
        $currentMetadata = $attachment->metadata ?: [];
        $newMetadata = array_merge($currentMetadata, $metadata);

        return $this->attachmentRepository->update($attachment, [
            'metadata' => $newMetadata
        ]);
    }

    /**
     * Move attachment to a different attachable model.
     */
    public function moveToAttachable(Attachment $attachment, string $attachableType, int $attachableId): bool
    {
        return $this->attachmentRepository->update($attachment, [
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId
        ]);
    }

    /**
     * Get attachments by attachable model.
     */
    public function getByAttachable(string $attachableType, int $attachableId): Collection
    {
        return $this->attachmentRepository->getByAttachable($attachableType, $attachableId);
    }

    /**
     * Get attachments by MIME type (e.g., 'image', 'video', 'document').
     */
    public function getByMimeType(string $mimeType): Collection
    {
        return $this->attachmentRepository->getByMimeType($mimeType);
    }
}
