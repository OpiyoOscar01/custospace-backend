<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventParticipantRequest;
use App\Http\Requests\UpdateEventParticipantRequest;
use App\Http\Resources\EventParticipantResource;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\EventParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Event Participant API Controller
 * 
 * Handles HTTP requests for event participant management
 */
class EventParticipantController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private EventParticipantService $participantService
    ) {
    }

    /**
     * Display participants for an event
     * 
     * @param Event $event
     * @return AnonymousResourceCollection
     */
    public function index(Event $event): AnonymousResourceCollection
    {
        $this->authorize('view', $event);

        $participants = $this->participantService->getEventParticipants($event->id);

        return EventParticipantResource::collection($participants);
    }

    /**
     * Add a participant to an event
     * 
     * @param CreateEventParticipantRequest $request
     * @param Event $event
     * @return EventParticipantResource
     */
    public function store(CreateEventParticipantRequest $request, Event $event): EventParticipantResource
    {
        $this->authorize('update', $event);

        $participant = $this->participantService->addParticipant(
            $event->id,
            $request->get('user_id'),
            $request->get('status', 'pending')
        );

        return new EventParticipantResource($participant);
    }

    /**
     * Display a specific event participant
     * 
     * @param Event $event
     * @param EventParticipant $participant
     * @return EventParticipantResource
     */
    public function show(Event $event, EventParticipant $participant): EventParticipantResource
    {
        $this->authorize('view', $event);

        // Ensure participant belongs to the event
        if ($participant->event_id !== $event->id) {
            abort(404, 'Participant not found for this event');
        }

        return new EventParticipantResource($participant->load(['user', 'event']));
    }

    /**
     * Update participant status
     * 
     * @param UpdateEventParticipantRequest $request
     * @param Event $event
     * @param EventParticipant $participant
     * @return EventParticipantResource
     */
    public function update(UpdateEventParticipantRequest $request, Event $event, EventParticipant $participant): EventParticipantResource
    {
        // Users can update their own participation status, or event organizers can update any participant
        $this->authorize('updateParticipant', [$event, $participant]);

        // Ensure participant belongs to the event
        if ($participant->event_id !== $event->id) {
            abort(404, 'Participant not found for this event');
        }

        $updatedParticipant = $this->participantService->updateParticipantStatus(
            $participant,
            $request->get('status')
        );

        return new EventParticipantResource($updatedParticipant);
    }

    /**
     * Remove a participant from an event
     * 
     * @param Event $event
     * @param EventParticipant $participant
     * @return JsonResponse
     */
    public function destroy(Event $event, EventParticipant $participant): JsonResponse
    {
        $this->authorize('update', $event);

        // Ensure participant belongs to the event
        if ($participant->event_id !== $event->id) {
            abort(404, 'Participant not found for this event');
        }

        $this->participantService->removeParticipant($participant);

        return response()->json([
            'message' => 'Participant removed successfully'
        ]);
    }

    /**
     * Accept event invitation
     * 
     * @param Event $event
     * @param EventParticipant $participant
     * @return EventParticipantResource
     */
    public function accept(Event $event, EventParticipant $participant): EventParticipantResource
    {
        // Only the participant themselves can accept their invitation
        if ($participant->user_id !== Auth::id()) {
            abort(403, 'You can only accept your own invitations');
        }

        if ($participant->event_id !== $event->id) {
            abort(404, 'Participant not found for this event');
        }

        $updatedParticipant = $this->participantService->acceptInvitation($participant);

        return new EventParticipantResource($updatedParticipant);
    }

    /**
     * Decline event invitation
     * 
     * @param Event $event
     * @param EventParticipant $participant
     * @return EventParticipantResource
     */
    public function decline(Event $event, EventParticipant $participant): EventParticipantResource
    {
        // Only the participant themselves can decline their invitation
        if ($participant->user_id !== Auth::id()) {
            abort(403, 'You can only decline your own invitations');
        }

        if ($participant->event_id !== $event->id) {
            abort(404, 'Participant not found for this event');
        }

        $updatedParticipant = $this->participantService->declineInvitation($participant);

        return new EventParticipantResource($updatedParticipant);
    }

    /**
     * Mark participation as tentative
     * 
     * @param Event $event
     * @param EventParticipant $participant
     * @return EventParticipantResource
     */
    public function tentative(Event $event, EventParticipant $participant): EventParticipantResource
    {
        // Only the participant themselves can mark as tentative
        if ($participant->user_id !== Auth::id()) {
            abort(403, 'You can only update your own participation status');
        }

        if ($participant->event_id !== $event->id) {
            abort(404, 'Participant not found for this event');
        }

        $updatedParticipant = $this->participantService->markTentative($participant);

        return new EventParticipantResource($updatedParticipant);
    }

    /**
     * Get participants by status
     * 
     * @param Request $request
     * @param Event $event
     * @return AnonymousResourceCollection
     */
    public function byStatus(Request $request, Event $event): AnonymousResourceCollection
    {
        $this->authorize('view', $event);

        $request->validate([
            'status' => 'required|in:pending,accepted,declined,tentative'
        ]);

        $participants = $this->participantService->getParticipantsByStatus(
            $event->id,
            $request->get('status')
        );

        return EventParticipantResource::collection($participants);
    }

    /**
     * Get user's event participations
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function myParticipations(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'workspace_id']);
        
        $participations = $this->participantService->getUserParticipations(Auth::id(), $filters);

        return EventParticipantResource::collection($participations);
    }
}