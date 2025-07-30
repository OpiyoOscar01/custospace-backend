<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAttachmentRequest;
use App\Http\Requests\UpdateAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * API Controller for managing attachments.
 */
class AttachmentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Constructor to inject service dependency.
     */
    public function __construct(
        private AttachmentService $attachmentService
    ) {
        $this->authorizeResource(Attachment::class, 'attachment');
    }

    /**
     * Display a listing of attachments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $attachments = $this->attachmentService->getAllPaginated($perPage);

        return AttachmentResource::collection($attachments);
    }

    /**
     * Store a newly created attachment.
     */
    public function store(CreateAttachmentRequest $request): AttachmentResource
    {
        $attachment = $this->attachmentService->create($request->validated());

        return new AttachmentResource($attachment);
    }

    /**
     * Display the specified attachment.
     */
    public function show(Attachment $attachment): AttachmentResource
    {
        return new AttachmentResource($attachment->load(['user', 'attachable']));
    }

    /**
     * Update the specified attachment.
     */
    public function update(UpdateAttachmentRequest $request, Attachment $attachment): AttachmentResource
    {
        $this->attachmentService->update($attachment, $request->validated());

        return new AttachmentResource($attachment->fresh(['user', 'attachable']));
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->attachmentService->delete($attachment);

        return response()->json(['message' => 'Attachment deleted successfully.']);
    }

    /**
     * Download the attachment file.
     */
    public function download(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        try {
            return $this->attachmentService->download($attachment);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update attachment metadata.
     */
    public function updateMetadata(Request $request, Attachment $attachment): AttachmentResource
    {
        $this->authorize('update', $attachment);

        $request->validate([
            'metadata' => 'required|array'
        ]);

        $this->attachmentService->updateMetadata($attachment, $request->input('metadata'));

        return new AttachmentResource($attachment->fresh(['user', 'attachable']));
    }

    /**
     * Move attachment to different attachable model.
     */
    public function moveToAttachable(Request $request, Attachment $attachment): AttachmentResource
    {
        $this->authorize('update', $attachment);

        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer|min:1'
        ]);

        $this->attachmentService->moveToAttachable(
            $attachment,
            $request->input('attachable_type'),
            $request->input('attachable_id')
        );

        return new AttachmentResource($attachment->fresh(['user', 'attachable']));
    }
}
