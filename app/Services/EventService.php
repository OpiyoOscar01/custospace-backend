<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\EventParticipantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Event Service
 * 
 * Handles all business logic for event operations
 */
class EventService
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private EventParticipantRepositoryInterface $participantRepository
    ) {}

    /**
     * Get events for a workspace with filtering
     */
    public function getWorkspaceEvents(int $workspaceId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->eventRepository->getByWorkspace($workspaceId, $filters, $perPage);
    }

    /**
     * Get calendar events for date range
     */
    public function getCalendarEvents(int $workspaceId, string $startDate, string $endDate): Collection
    {
        return $this->eventRepository->getByDateRange($workspaceId, $startDate, $endDate);
    }

    /**
     * Create a new event with participants
     */
    public function createEvent(array $data, int $createdById): Event
    {
        try {
            return DB::transaction(function () use ($data, $createdById) {
                $data['created_by_id'] = $createdById;
                
                $event = $this->eventRepository->create($data);
                
                Log::info('Event created successfully', [
                    'event_id' => $event->id,
                    'created_by' => $createdById,
                    'workspace_id' => $data['workspace_id']
                ]);

                return $event;
            });
        } catch (\Exception $e) {
            Log::error('Failed to create event', [
                'error' => $e->getMessage(),
                'data' => $data,
                'created_by' => $createdById
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing event
     */
    public function updateEvent(Event $event, array $data): Event
    {
        try {
            $updatedEvent = $this->eventRepository->update($event, $data);
            
            Log::info('Event updated successfully', [
                'event_id' => $event->id,
                'changes' => $data
            ]);

            return $updatedEvent;
        } catch (\Exception $e) {
            Log::error('Failed to update event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete an event
     */
    public function deleteEvent(Event $event): bool
    {
        try {
            $result = $this->eventRepository->delete($event);
            
            Log::info('Event deleted successfully', [
                'event_id' => $event->id
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to delete event', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add participants to event
     */
    public function addParticipants(Event $event, array $userIds): Collection
    {
        try {
            return DB::transaction(function () use ($event, $userIds) {
                $participants = $this->participantRepository->bulkAddParticipants(
                    $event->id,
                    $userIds,
                    'pending'
                );

                Log::info('Participants added to event', [
                    'event_id' => $event->id,
                    'participant_count' => $participants->count(),
                    'user_ids' => $userIds
                ]);

                return $participants;
            });
        } catch (\Exception $e) {
            Log::error('Failed to add participants to event', [
                'event_id' => $event->id,
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get user's events with optional filters
     */
    public function getUserEvents(int $userId, array $filters = []): Collection
    {
        return $this->eventRepository->getUserEvents($userId, $filters);
    }

    /**
     * Get upcoming events for workspace
     */
    public function getUpcomingEvents(int $workspaceId, int $limit = 10): Collection
    {
        return $this->eventRepository->getUpcoming($workspaceId, $limit);
    }

    /**
     * Cancel an event (soft approach - update metadata)
     */
    public function cancelEvent(Event $event, ?string $reason = null): Event
    {
        $metadata = $event->metadata ?? [];
        $metadata['cancelled'] = true;
        $metadata['cancelled_at'] = now()->toISOString();
        
        if ($reason) {
            $metadata['cancellation_reason'] = $reason;
        }

        return $this->updateEvent($event, ['metadata' => $metadata]);
    }

    /**
     * Reschedule an event
     */
    public function rescheduleEvent(Event $event, string $newStartDate, string $newEndDate): Event
    {
        $metadata = $event->metadata ?? [];
        $metadata['rescheduled'] = true;
        $metadata['original_start_date'] = $event->start_date->toISOString();
        $metadata['original_end_date'] = $event->end_date->toISOString();
        $metadata['rescheduled_at'] = now()->toISOString();

        return $this->updateEvent($event, [
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
            'metadata' => $metadata
        ]);
    }
}