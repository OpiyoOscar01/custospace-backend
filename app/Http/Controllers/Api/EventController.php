<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Event API Controller
 * 
 * Handles HTTP requests for event management
 */
class EventController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private EventService $eventService
    ) {
    }

    /**
     * Display a listing of events for the workspace
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Event::class);

        $workspaceId = $request->get('workspace_id');
        $filters = $request->only(['type', 'start_date', 'end_date', 'search']);
        $perPage = $request->get('per_page', 15);

        $events = $this->eventService->getWorkspaceEvents($workspaceId, $filters, $perPage);

        return EventResource::collection($events);
    }

    /**
     * Store a newly created event
     * 
     * @param CreateEventRequest $request
     * @return EventResource
     */
    public function store(CreateEventRequest $request): EventResource
    {
        $this->authorize('create', Event::class);

        $event = $this->eventService->createEvent(
            $request->validated(),
            Auth::id()
        );

        return new EventResource($event);
    }

    /**
     * Display the specified event
     * 
     * @param Event $event
     * @return EventResource
     */
    public function show(Event $event): EventResource
    {
        $this->authorize('view', $event);

        $event->load(['createdBy', 'participants.user', 'workspace']);

        return new EventResource($event);
    }

    /**
     * Update the specified event
     * 
     * @param UpdateEventRequest $request
     * @param Event $event
     * @return EventResource
     */
    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $updatedEvent = $this->eventService->updateEvent($event, $request->validated());

        return new EventResource($updatedEvent);
    }

    /**
     * Remove the specified event
     * 
     * @param Event $event
     * @return JsonResponse
     */
    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $this->eventService->deleteEvent($event);

        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }

    /**
     * Get calendar events for date range
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function calendar(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Event::class);

        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $events = $this->eventService->getCalendarEvents(
            $request->get('workspace_id'),
            $request->get('start_date'),
            $request->get('end_date')
        );

        return EventResource::collection($events);
    }

    /**
     * Add participants to event
     * 
     * @param Request $request
     * @param Event $event
     * @return JsonResponse
     */
    public function addParticipants(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $participants = $this->eventService->addParticipants(
            $event,
            $request->get('user_ids')
        );

        return response()->json([
            'message' => 'Participants added successfully',
            'participants_count' => $participants->count()
        ]);
    }

    /**
     * Cancel an event
     * 
     * @param Request $request
     * @param Event $event
     * @return EventResource
     */
    public function cancel(Request $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $cancelledEvent = $this->eventService->cancelEvent(
            $event,
            $request->get('reason')
        );

        return new EventResource($cancelledEvent);
    }

    /**
     * Reschedule an event
     * 
     * @param Request $request
     * @param Event $event
     * @return EventResource
     */
    public function reschedule(Request $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $rescheduledEvent = $this->eventService->rescheduleEvent(
            $event,
            $request->get('start_date'),
            $request->get('end_date')
        );

        return new EventResource($rescheduledEvent);
    }

    /**
     * Get upcoming events for workspace
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function upcoming(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Event::class);

        $request->validate([
            'workspace_id' => 'required|integer|exists:workspaces,id',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $events = $this->eventService->getUpcomingEvents(
            $request->get('workspace_id'),
            $request->get('limit', 10)
        );

        return EventResource::collection($events);
    }

    /**
     * Get user's events
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function myEvents(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['workspace_id', 'status']);
        
        $events = $this->eventService->getUserEvents(Auth::id(), $filters);

        return EventResource::collection($events);
    }
}