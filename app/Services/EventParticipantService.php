<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Repositories\Contracts\EventParticipantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Event Participant Service
 * 
 * Handles all business logic for event participant operations
 */
class EventParticipantService
{
    public function __construct(
        private EventParticipantRepositoryInterface $participantRepository
    ) {}

    /**
     * Get participants for an event
     */
    public function getEventParticipants(int $eventId): Collection
    {
        return $this->participantRepository->getByEvent($eventId);
    }

    /**
     * Get user's event participations
     */
    public function getUserParticipations(int $userId, array $filters = []): Collection
    {
        return $this->participantRepository->getByUser($userId, $filters);
    }

    /**
     * Add participant to event
     */
    public function addParticipant(int $eventId, int $userId, string $status = 'pending'): EventParticipant
    {
        try {
            // Check if user is already a participant
            if ($this->participantRepository->isParticipant($eventId, $userId)) {
                throw new \InvalidArgumentException('User is already a participant in this event');
            }

            $participant = $this->participantRepository->addParticipant($eventId, $userId, $status);
            
            Log::info('Participant added to event', [
                'event_id' => $eventId,
                'user_id' => $userId,
                'status' => $status
            ]);

            return $participant;
        } catch (\Exception $e) {
            Log::error('Failed to add participant to event', [
                'event_id' => $eventId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update participant status (accept/decline/tentative)
     */
    public function updateParticipantStatus(EventParticipant $participant, string $status): EventParticipant
    {
        try {
            $oldStatus = $participant->status;
            $updatedParticipant = $this->participantRepository->updateStatus($participant, $status);
            
            Log::info('Participant status updated', [
                'participant_id' => $participant->id,
                'event_id' => $participant->event_id,
                'user_id' => $participant->user_id,
                'old_status' => $oldStatus,
                'new_status' => $status
            ]);

            return $updatedParticipant;
        } catch (\Exception $e) {
            Log::error('Failed to update participant status', [
                'participant_id' => $participant->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove participant from event
     */
    public function removeParticipant(EventParticipant $participant): bool
    {
        try {
            $result = $this->participantRepository->removeParticipant($participant);
            
            Log::info('Participant removed from event', [
                'participant_id' => $participant->id,
                'event_id' => $participant->event_id,
                'user_id' => $participant->user_id
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to remove participant from event', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get participants by status
     */
    public function getParticipantsByStatus(int $eventId, string $status): Collection
    {
        return $this->participantRepository->getByStatus($eventId, $status);
    }

    /**
     * Accept event invitation
     */
    public function acceptInvitation(EventParticipant $participant): EventParticipant
    {
        return $this->updateParticipantStatus($participant, 'accepted');
    }

    /**
     * Decline event invitation
     */
    public function declineInvitation(EventParticipant $participant): EventParticipant
    {
        return $this->updateParticipantStatus($participant, 'declined');
    }

    /**
     * Mark participation as tentative
     */
    public function markTentative(EventParticipant $participant): EventParticipant
    {
        return $this->updateParticipantStatus($participant, 'tentative');
    }

    /**
     * Bulk add participants to event
     */
    public function bulkAddParticipants(int $eventId, array $userIds, string $status = 'pending'): Collection
    {
        try {
            $participants = $this->participantRepository->bulkAddParticipants($eventId, $userIds, $status);
            
            Log::info('Bulk participants added to event', [
                'event_id' => $eventId,
                'participant_count' => $participants->count(),
                'user_ids' => $userIds,
                'status' => $status
            ]);

            return $participants;
        } catch (\Exception $e) {
            Log::error('Failed to bulk add participants to event', [
                'event_id' => $eventId,
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}