<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Http\Resources\FormResource;
use App\Models\Form;
use App\Services\FormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\FormResponseService;
use App\Repositories\Contracts\FormResponseRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Form API Controller
 * 
 * Handles HTTP requests for form management
 * Supports CRUD operations and custom actions
 */
class FormController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private FormService $formService
    ) {
    }

    /**
     * Display a listing of forms
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Form::class);

        $filters = $request->only([
            'workspace_id', 'is_active', 'created_by_id', 'search'
        ]);

        $perPage = $request->get('per_page', 15);
        $forms = $this->formService->getPaginatedForms($filters, $perPage);

        return FormResource::collection($forms);
    }

    /**
     * Store a newly created form
     * 
     * @param CreateFormRequest $request
     * @return FormResource
     */
    public function store(CreateFormRequest $request): FormResource
    {
        $this->authorize('create', Form::class);

        $data = $request->validated();
        $data['created_by_id'] = \Auth::id();

        $form = $this->formService->createForm($data);

        return new FormResource($form);
    }

    /**
     * Display the specified form
     * 
     * @param Form $form
     * @return FormResource
     */
    public function show(Form $form): FormResource
    {
        $this->authorize('view', $form);

        return new FormResource($form);
    }

    /**
     * Update the specified form
     * 
     * @param UpdateFormRequest $request
     * @param Form $form
     * @return FormResource
     */
    public function update(UpdateFormRequest $request, Form $form): FormResource
    {
        $this->authorize('update', $form);

        $data = $request->validated();
        $updatedForm = $this->formService->updateForm($form, $data);

        return new FormResource($updatedForm);
    }

    /**
     * Remove the specified form
     * 
     * @param Form $form
     * @return JsonResponse
     */
    public function destroy(Form $form): JsonResponse
    {
        $this->authorize('delete', $form);

        $this->formService->deleteForm($form);

        return response()->json([
            'message' => 'Form deleted successfully'
        ]);
    }

    /**
     * Activate a form
     * 
     * @param Form $form
     * @return FormResource
     */
    public function activate(Form $form): FormResource
    {
        $this->authorize('update', $form);

        $activatedForm = $this->formService->activateForm($form);

        return new FormResource($activatedForm);
    }

    /**
     * Deactivate a form
     * 
     * @param Form $form
     * @return FormResource
     */
    public function deactivate(Form $form): FormResource
    {
        $this->authorize('update', $form);

        $deactivatedForm = $this->formService->deactivateForm($form);

        return new FormResource($deactivatedForm);
    }

    /**
     * Duplicate a form
     * 
     * @param Request $request
     * @param Form $form
     * @return FormResource
     */
    public function duplicate(Request $request, Form $form): FormResource
    {
        $this->authorize('create', Form::class);
        $this->authorize('view', $form);

        $overrides = $request->only(['name', 'workspace_id']);
        $overrides['created_by_id'] = \Auth::id();

        $duplicatedForm = $this->formService->duplicateForm($form, $overrides);

        return new FormResource($duplicatedForm);
    }

    /**
     * Get form analytics
     * 
     * @param Form $form
     * @return JsonResponse
     */
    public function analytics(Form $form): JsonResponse
    {
        $this->authorize('view', $form);

        $analytics = app(FormResponseService::class)->getResponseAnalytics($form);

        return response()->json([
            'data' => $analytics
        ]);
    }

    /**
     * Export form responses
     * 
     * @param Form $form
     * @return JsonResponse
     */
    public function export(Form $form): JsonResponse
    {
        $this->authorize('view', $form);

        $csv = app(FormResponseService::class)->exportResponsesToCsv($form);
        $filename = "form-{$form->slug}-responses-" . now()->format('Y-m-d') . '.csv';

        return response()->json([
            'data' => [
                'filename' => $filename,
                'content' => base64_encode($csv),
                'mime_type' => 'text/csv'
            ]
        ]);
    }
}
