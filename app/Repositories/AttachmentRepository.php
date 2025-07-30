<?php

namespace App\Repositories;

use App\Models\Attachment;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Attachment Repository Implementation
 */
class AttachmentRepository implements AttachmentRepositoryInterface
{
    /**
     * Get all attachments with pagination.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Attachment::with(['user', 'attachable'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find attachment by ID.
     */
    public function findById(int $id): ?Attachment
    {
        return Attachment::with(['user', 'attachable'])->find($id);
    }

    /**
     * Create a new attachment.
     */
    public function create(array $data): Attachment
    {
        return Attachment::create($data);
    }

    /**
     * Update an existing attachment.
     */
    public function update(Attachment $attachment, array $data): bool
    {
        return $attachment->update($data);
    }

    /**
     * Delete an attachment.
     */
    public function delete(Attachment $attachment): bool
    {
        // Delete the physical file
        \Storage::disk($attachment->disk)->delete($attachment->path);
        
        return $attachment->delete();
    }

    /**
     * Get attachments by attachable model.
     */
    public function getByAttachable(string $attachableType, int $attachableId): Collection
    {
        return Attachment::where('attachable_type', $attachableType)
            ->where('attachable_id', $attachableId)
            ->with('user')
            ->get();
    }

    /**
     * Get attachments by user.
     */
    public function getByUser(int $userId): Collection
    {
        return Attachment::where('user_id', $userId)
            ->with('attachable')
            ->latest()
            ->get();
    }

    /**
     * Get attachments by MIME type.
     */
    public function getByMimeType(string $mimeType): Collection
    {
        return Attachment::where('mime_type', 'like', $mimeType . '%')
            ->with(['user', 'attachable'])
            ->latest()
            ->get();
    }
}
