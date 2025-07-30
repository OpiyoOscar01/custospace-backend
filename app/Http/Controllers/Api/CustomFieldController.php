<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomFieldRequest;
use App\Http\Requests\UpdateCustomFieldRequest;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use App\Services\CustomFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Custom Field API Controller
 * 
 * Handles CRUD operations and custom actions for custom fields
 */
class CustomFieldController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private CustomFieldService $customFieldService
    ) {
        $this->authorizeResource(CustomField::class, 'customField');
    }

    /**
     * Display a listing of custom fields
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $customFields = $this->customFieldService->getAllPaginated(
            $request->input('workspace_id'),
            $request->input('applies_to'),
            $request->input('per_page', 15)
        );

        return CustomFieldResource::collection($customFields);
    }

    /**
     * Store a newly created custom field
     */
    public function store(CreateCustomFieldRequest $request): CustomFieldResource
    {
        $customField = $this->customFieldService->create($request->validated());

        return new CustomFieldResource($customField);
    }

    /**
     * Display the specified custom field
     */
    public function show(CustomField $customField): CustomFieldResource
    {
        return new CustomFieldResource($customField->load(['customFieldValues', 'workspace']));
    }

    /**
     * Update the specified custom field
     */
    public function update(UpdateCustomFieldRequest $request, CustomField $customField): CustomFieldResource
    {
        $updatedCustomField = $this->customFieldService->update($customField, $request->validated());

        return new CustomFieldResource($updatedCustomField);
    }

    /**
     * Remove the specified custom field
     */
    public function destroy(CustomField $customField): JsonResponse
    {
        $this->customFieldService->delete($customField);

        return response()->json([
            'message' => 'Custom field deleted successfully'
        ]);
    }

    /**
     * Bulk update the order of custom fields
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate([
            'fields' => ['required', 'array'],
            'fields.*.id' => ['required', 'exists:custom_fields,id'],
            'fields.*.order' => ['required', 'integer', 'min:0'],
        ]);

        $this->customFieldService->updateOrder($request->input('fields'));

        return response()->json([
            'message' => 'Custom field order updated successfully'
        ]);
    }

    /**
     * Duplicate a custom field
     */
    public function duplicate(CustomField $customField): CustomFieldResource
    {
        $this->authorize('create', CustomField::class);
        
        $duplicatedField = $this->customFieldService->duplicate($customField);

        return new CustomFieldResource($duplicatedField);
    }

    /**
     * Get custom fields by applies_to and workspace
     */
    public function getByEntity(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'applies_to' => ['required', 'string'],
        ]);

        $customFields = $this->customFieldService->getByEntity(
            $request->input('workspace_id'),
            $request->input('applies_to')
        );

        return CustomFieldResource::collection($customFields);
    }
}
