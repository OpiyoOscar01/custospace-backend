<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvitationRequest;
use App\Http\Requests\UpdateInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class InvitationController
 * 
 * Handles API endpoints for invitation management
 * 
 * @package App\Http\Controllers\Api
 */
class InvitationController extends Controller
{
    use AuthorizesRequests;
    /**
     * InvitationController constructor.
     */
    public function __construct(
        protected InvitationService $invitationService
    ) {
        $this->authorizeResource(Invitation::class, 'invitation');
    }

    /**
     * Display a listing of invitations
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $invitations = $this->invitationService->getAllInvitations(
            $request->only(['workspace_id', 'status', 'role', 'per_page'])
        );

        return InvitationResource::collection($invitations);
    }

    /**
     * Store a newly created invitation
     * 
     * @param CreateInvitationRequest $request
     * @return InvitationResource
     */
    public function store(CreateInvitationRequest $request): InvitationResource
    {
        $invitation = $this->invitationService->createInvitation(
            $request->validated(),
            $request->user()
        );

        return new InvitationResource($invitation);
    }

    /**
     * Display the specified invitation
     * 
     * @param Invitation $invitation
     * @return InvitationResource
     */
    public function show(Invitation $invitation): InvitationResource
    {
        return new InvitationResource(
            $invitation->load(['workspace', 'team', 'invitedBy'])
        );
    }

    /**
     * Update the specified invitation
     * 
     * @param UpdateInvitationRequest $request
     * @param Invitation $invitation
     * @return InvitationResource
     */
    public function update(UpdateInvitationRequest $request, Invitation $invitation): InvitationResource
    {
        $updatedInvitation = $this->invitationService->updateInvitation(
            $invitation,
            $request->validated()
        );

        return new InvitationResource($updatedInvitation);
    }

    /**
     * Remove the specified invitation
     * 
     * @param Invitation $invitation
     * @return JsonResponse
     */
    public function destroy(Invitation $invitation): JsonResponse
    {
        $this->invitationService->deleteInvitation($invitation);

        return response()->json([
            'message' => 'Invitation deleted successfully'
        ]);
    }

    /**
     * Accept an invitation
     * 
     * @param Request $request
     * @param Invitation $invitation
     * @return JsonResponse
     */
    public function accept(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorize('accept', $invitation);

        $result = $this->invitationService->acceptInvitation(
            $invitation,
            $request->user()
        );

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'data' => $result
        ]);
    }

    /**
     * Decline an invitation
     * 
     * @param Request $request
     * @param Invitation $invitation
     * @return JsonResponse
     */
    public function decline(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorize('decline', $invitation);

        $this->invitationService->declineInvitation($invitation);

        return response()->json([
            'message' => 'Invitation declined successfully'
        ]);
    }

    /**
     * Resend an invitation
     * 
     * @param Invitation $invitation
     * @return JsonResponse
     */
    public function resend(Invitation $invitation): JsonResponse
    {
        $this->authorize('resend', $invitation);

        $this->invitationService->resendInvitation($invitation);

        return response()->json([
            'message' => 'Invitation resent successfully'
        ]);
    }

    /**
     * Bulk delete invitations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'invitation_ids' => ['required', 'array'],
            'invitation_ids.*' => ['integer', 'exists:invitations,id']
        ]);

        $deletedCount = $this->invitationService->bulkDeleteInvitations(
            $request->invitation_ids,
            $request->user()
        );

        return response()->json([
            'message' => "{$deletedCount} invitations deleted successfully"
        ]);
    }
}