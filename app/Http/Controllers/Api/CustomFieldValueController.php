<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomFieldValueRequest;
use App\Http\Requests\UpdateCustomFieldValueRequest;
use App\Http\Resources\CustomFieldValueResource;
use App\Models\CustomFieldValue;
use App\Services\CustomFieldValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


/**
 * Custom Field Value API Controller
 * 
 * Handles CRUD operations for custom field values
 */
class CustomFieldValueController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private CustomFieldValueService $customFieldValueService
    ) {
        $this->authorizeResource(CustomFieldValue::class, 'customFieldValue');
    }

    /**
     * Display a listing of custom field values
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'custom_field_id' => ['sometimes', 'exists:custom_fields,id'],
            'entity_type' => ['sometimes', 'string'],
            'entity_id' => ['sometimes', 'integer'],
        ]);

        $customFieldValues = $this->customFieldValueService->getAllPaginated(
            $request->only(['custom_field_id', 'entity_type', 'entity_id']),
            $request->input('per_page', 15)
        );

        return CustomFieldValueResource::collection($customFieldValues);
    }

    /**
     * Store a newly created custom field value
     */
    public function store(CreateCustomFieldValueRequest $request): CustomFieldValueResource
    {
        $customFieldValue = $this->customFieldValueService->create($request->validated());

        return new CustomFieldValueResource($customFieldValue);
    }

    /**
     * Display the specified custom field value
     */
    public function show(CustomFieldValue $customFieldValue): CustomFieldValueResource
    {
        return new CustomFieldValueResource($customFieldValue->load(['customField', 'entity']));
    }

    /**
     * Update the specified custom field value
     */
    public function update(UpdateCustomFieldValueRequest $request, CustomFieldValue $customFieldValue): CustomFieldValueResource
    {
        $updatedCustomFieldValue = $this->customFieldValueService->update($customFieldValue, $request->validated());

        return new CustomFieldValueResource($updatedCustomFieldValue);
    }

    /**
     * Remove the specified custom field value
     */
    public function destroy(CustomFieldValue $customFieldValue): JsonResponse
    {
        $this->customFieldValueService->delete($customFieldValue);

        return response()->json([
            'message' => 'Custom field value deleted successfully'
        ]);
    }

    /**
     * Bulk store/update custom field values for an entity
     */
    public function bulkStore(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'values' => ['required', 'array'],
            'values.*.custom_field_id' => ['required', 'exists:custom_fields,id'],
            'values.*.value' => ['nullable'],
        ]);

        $customFieldValues = $this->customFieldValueService->bulkStore(
            $request->input('entity_type'),
            $request->input('entity_id'),
            $request->input('values')
        );

        return CustomFieldValueResource::collection($customFieldValues);
    }

    /**
     * Get custom field values for a specific entity
     */
    public function getByEntity(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
        ]);

        $customFieldValues = $this->customFieldValueService->getByEntity(
            $request->input('entity_type'),
            $request->input('entity_id')
        );

        return CustomFieldValueResource::collection($customFieldValues);
    }
}
