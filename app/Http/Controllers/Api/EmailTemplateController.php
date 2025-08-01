<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmailTemplateRequest;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Http\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class EmailTemplateController
 * 
 * Handles API endpoints for email template management
 * 
 * @package App\Http\Controllers\Api
 */
class EmailTemplateController extends Controller
{
    use AuthorizesRequests;
    /**
     * EmailTemplateController constructor.
     */
    public function __construct(
        protected EmailTemplateService $emailTemplateService
    ) {
        $this->authorizeResource(EmailTemplate::class, 'email_template');
    }

    /**
     * Display a listing of email templates
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = $this->emailTemplateService->getAllTemplates(
            $request->only(['workspace_id', 'type', 'is_active', 'search', 'per_page'])
        );

        return EmailTemplateResource::collection($templates);
    }

    /**
     * Store a newly created email template
     * 
     * @param CreateEmailTemplateRequest $request
     * @return EmailTemplateResource
     */
    public function store(CreateEmailTemplateRequest $request): EmailTemplateResource
    {
        $template = $this->emailTemplateService->createTemplate($request->validated());

        return new EmailTemplateResource($template);
    }

    /**
     * Display the specified email template
     * 
     * @param EmailTemplate $emailTemplate
     * @return EmailTemplateResource
     */
    public function show(EmailTemplate $emailTemplate): EmailTemplateResource
    {
        return new EmailTemplateResource(
            $emailTemplate->load(['workspace'])
        );
    }

    /**
     * Update the specified email template
     * 
     * @param UpdateEmailTemplateRequest $request
     * @param EmailTemplate $emailTemplate
     * @return EmailTemplateResource
     */
    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): EmailTemplateResource
    {
        $updatedTemplate = $this->emailTemplateService->updateTemplate(
            $emailTemplate,
            $request->validated()
        );

        return new EmailTemplateResource($updatedTemplate);
    }

    /**
     * Remove the specified email template
     * 
     * @param EmailTemplate $emailTemplate
     * @return JsonResponse
     */
    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->emailTemplateService->deleteTemplate($emailTemplate);

        return response()->json([
            'message' => 'Email template deleted successfully'
        ]);
    }

    /**
     * Activate an email template
     * 
     * @param EmailTemplate $emailTemplate
     * @return JsonResponse
     */
    public function activate(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('update', $emailTemplate);

        $this->emailTemplateService->activateTemplate($emailTemplate);

        return response()->json([
            'message' => 'Email template activated successfully'
        ]);
    }

    /**
     * Deactivate an email template
     * 
     * @param EmailTemplate $emailTemplate
     * @return JsonResponse
     */
    public function deactivate(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('update', $emailTemplate);

        $this->emailTemplateService->deactivateTemplate($emailTemplate);

        return response()->json([
            'message' => 'Email template deactivated successfully'
        ]);
    }

    /**
     * Duplicate an email template
     * 
     * @param EmailTemplate $emailTemplate
     * @return EmailTemplateResource
     */
    public function duplicate(EmailTemplate $emailTemplate): EmailTemplateResource
    {
        $this->authorize('create', EmailTemplate::class);

        $duplicatedTemplate = $this->emailTemplateService->duplicateTemplate($emailTemplate);

        return new EmailTemplateResource($duplicatedTemplate);
    }

    /**
     * Preview compiled template
     * 
     * @param Request $request
     * @param EmailTemplate $emailTemplate
     * @return JsonResponse
     */
    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('view', $emailTemplate);

        $request->validate([
            'variables' => ['array'],
            'variables.*' => ['string']
        ]);

        $compiledContent = $this->emailTemplateService->previewTemplate(
            $emailTemplate,
            $request->input('variables', [])
        );

        return response()->json([
            'subject' => $emailTemplate->subject,
            'content' => $compiledContent
        ]);
    }
}