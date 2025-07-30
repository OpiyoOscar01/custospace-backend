<?php

namespace App\Repositories\Contracts;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for Attachment Repository
 */
interface AttachmentRepositoryInterface
{
    /**
     * Get all attachments with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find attachment by ID.
     */
    public function findById(int $id): ?Attachment;

    /**
     * Create a new attachment.
     */
    public function create(array $data): Attachment;

    /**
     * Update an existing attachment.
     */
    public function update(Attachment $attachment, array $data): bool;

    /**
     * Delete an attachment.
     */
    public function delete(Attachment $attachment): bool;

    /**
     * Get attachments by attachable model.
     */
    public function getByAttachable(string $attachableType, int $attachableId): Collection;

    /**
     * Get attachments by user.
     */
    public function getByUser(int $userId): Collection;

    /**
     * Get attachments by MIME type.
     */
    public function getByMimeType(string $mimeType): Collection;
}
