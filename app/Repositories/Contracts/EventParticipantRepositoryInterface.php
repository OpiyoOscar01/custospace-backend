<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Event Participant Repository Interface
 * 
 * Defines contract for event participant data access operations
 */
interface EventParticipantRepositoryInterface
{
    /**
     * Get participants for an event
     */
    public function getByEvent(int $eventId): Collection;

    /**
     * Get user's event participations
     */
    public function getByUser(int $userId, array $filters = []): Collection;

    /**
     * Add participant to event
     */
    public function addParticipant(int $eventId, int $userId, string $status = 'pending'): EventParticipant;

    /**
     * Update participant status
     */
    public function updateStatus(EventParticipant $participant, string $status): EventParticipant;

    /**
     * Remove participant from event
     */
    public function removeParticipant(EventParticipant $participant): bool;

    /**
     * Bulk add participants
     */
    public function bulkAddParticipants(int $eventId, array $userIds, string $status = 'pending'): Collection;

    /**
     * Get participants by status
     */
    public function getByStatus(int $eventId, string $status): Collection;

    /**
     * Check if user is participant
     */
    public function isParticipant(int $eventId, int $userId): bool;
}