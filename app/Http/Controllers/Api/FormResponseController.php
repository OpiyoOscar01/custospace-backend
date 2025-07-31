<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFormResponseRequest;
use App\Http\Requests\UpdateFormResponseRequest;
use App\Http\Resources\FormResponseResource;
use App\Models\FormResponse;
use App\Services\FormResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Repositories\Contracts\FormResponseRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Form Response API Controller
 * 
 * Handles HTTP requests for form response management
 * Supports CRUD operations and response analytics
 */
class FormResponseController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private FormResponseService $formResponseService
    ) {
        // Some endpoints allow guest access for form submissions
    }

    /**
     * Display a listing of form responses
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FormResponse::class);

        $filters = $request->only([
            'form_id', 'user_id', 'date_from', 'date_to', 'has_user'
        ]);

        $perPage = $request->get('per_page', 15);
        $responses = $this->formResponseService->getPaginatedResponses($filters, $perPage);

        return FormResponseResource::collection($responses);
    }

    /**
     * Store a newly created form response
     * 
     * @param CreateFormResponseRequest $request
     * @return FormResponseResource
     */
    public function store(CreateFormResponseRequest $request): FormResponseResource
    {
        // Authorization is handled in the service/policy based on form settings
        $data = $request->validated();
        
        // Add authenticated user if available
        if (\Auth::check()) {
            $data['user_id'] = \Auth::id();
        }

        $response = $this->formResponseService->createResponse($data, $request);

        return new FormResponseResource($response);
    }

    /**
     * Display the specified form response
     * 
     * @param FormResponse $formResponse
     * @return FormResponseResource
     */
    public function show(FormResponse $formResponse): FormResponseResource
    {
        $this->authorize('view', $formResponse);

        return new FormResponseResource($formResponse);
    }

    /**
     * Update the specified form response
     * 
     * @param UpdateFormResponseRequest $request
     * @param FormResponse $formResponse
     * @return FormResponseResource
     */
    public function update(UpdateFormResponseRequest $request, FormResponse $formResponse): FormResponseResource
    {
        $this->authorize('update', $formResponse);

        $data = $request->validated();
        $updatedResponse = $this->formResponseService->updateResponse($formResponse, $data);

        return new FormResponseResource($updatedResponse);
    }

    /**
     * Remove the specified form response
     * 
     * @param FormResponse $formResponse
     * @return JsonResponse
     */
    public function destroy(FormResponse $formResponse): JsonResponse
    {
        $this->authorize('delete', $formResponse);

        $this->formResponseService->deleteResponse($formResponse);

        return response()->json([
            'message' => 'Form response deleted successfully'
        ]);
    }

    /**
     * Get responses for a specific form
     * 
     * @param Request $request
     * @param int $formId
     * @return AnonymousResourceCollection
     */
    public function getByForm(Request $request, int $formId): AnonymousResourceCollection
    {
        $form = \App\Models\Form::findOrFail($formId);
        $this->authorize('view', $form);

        $filters = $request->only(['user_id', 'date_from', 'date_to', 'has_user']);
        $responses = $this->formResponseService->getFormResponses($formId, $filters);

        return FormResponseResource::collection($responses);
    }

    /**
     * Get responses by the authenticated user
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function getUserResponses(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['form_id', 'date_from', 'date_to']);
        $responses = $this->formResponseService->getUserResponses(\Auth::id(), $filters);

        return FormResponseResource::collection($responses);
    }

    /**
     * Bulk delete form responses
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'response_ids' => ['required', 'array', 'min:1'],
            'response_ids.*' => ['integer', 'exists:form_responses,id']
        ]);

        $responseIds = $request->get('response_ids');
        $deletedCount = 0;

        foreach ($responseIds as $responseId) {
            $response = FormResponse::find($responseId);
            if ($response && $this->authorize('delete', $response)) {
                $this->formResponseService->deleteResponse($response);
                $deletedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully deleted {$deletedCount} form responses"
        ]);
    }
}
