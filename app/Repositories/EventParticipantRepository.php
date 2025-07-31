<?php

namespace App\Repositories;

use App\Models\EventParticipant;
use App\Repositories\Contracts\EventParticipantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Event Participant Repository Implementation
 * 
 * Handles all event participant data access operations
 */
class EventParticipantRepository implements EventParticipantRepositoryInterface
{
    /**
     * Get participants for an event
     */
    public function getByEvent(int $eventId): Collection
    {
        return EventParticipant::with(['user', 'event'])
            ->byEvent($eventId)
            ->get();
    }

    /**
     * Get user's event participations
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = EventParticipant::with(['user', 'event.createdBy'])
            ->byUser($userId);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['workspace_id'])) {
            $query->whereHas('event', function ($q) use ($filters) {
                $q->where('workspace_id', $filters['workspace_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Add participant to event
     */
    public function addParticipant(int $eventId, int $userId, string $status = 'pending'): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $eventId,
            'user_id' => $userId,
            'status' => $status,
        ]);
    }

    /**
     * Update participant status
     */
    public function updateStatus(EventParticipant $participant, string $status): EventParticipant
    {
        $participant->update(['status' => $status]);
        return $participant->fresh(['user', 'event']);
    }

    /**
     * Remove participant from event
     */
    public function removeParticipant(EventParticipant $participant): bool
    {
        return $participant->delete();
    }

    /**
     * Bulk add participants
     */
    public function bulkAddParticipants(int $eventId, array $userIds, string $status = 'pending'): Collection
    {
        return DB::transaction(function () use ($eventId, $userIds, $status) {
            $participants = collect();
            
            foreach ($userIds as $userId) {
                // Check if participant already exists
                $existing = EventParticipant::where('event_id', $eventId)
                    ->where('user_id', $userId)
                    ->first();
                
                if (!$existing) {
                    $participant = $this->addParticipant($eventId, $userId, $status);
                    $participants->push($participant);
                }
            }

            return $participants->load(['user', 'event']);
        });
    }

    /**
     * Get participants by status
     */
    public function getByStatus(int $eventId, string $status): Collection
    {
        return EventParticipant::with(['user', 'event'])
            ->byEvent($eventId)
            ->byStatus($status)
            ->get();
    }

    /**
     * Check if user is participant
     */
    public function isParticipant(int $eventId, int $userId): bool
    {
        return EventParticipant::where('event_id', $eventId)
            ->where('user_id', $userId)
            ->exists();
    }
}