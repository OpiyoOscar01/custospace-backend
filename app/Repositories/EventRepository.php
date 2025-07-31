<?php

namespace App\Repositories;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Event Repository Implementation
 * 
 * Handles all event data access operations
 */
class EventRepository implements EventRepositoryInterface
{
    /**
     * Get paginated events for a workspace
     */
    public function getByWorkspace(int $workspaceId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Event::with(['createdBy', 'participants.user'])
            ->byWorkspace($workspaceId);

        // Apply filters
        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('location', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('start_date', 'asc')->paginate($perPage);
    }

    /**
     * Get events by date range
     */
    public function getByDateRange(int $workspaceId, string $startDate, string $endDate): Collection
    {
        return Event::with(['createdBy', 'participants.user'])
            ->byWorkspace($workspaceId)
            ->byDateRange($startDate, $endDate)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get events by type
     */
    public function getByType(int $workspaceId, string $type): Collection
    {
        return Event::with(['createdBy', 'participants.user'])
            ->byWorkspace($workspaceId)
            ->byType($type)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get user's events
     */
    public function getUserEvents(int $userId, array $filters = []): Collection
    {
        $query = Event::with(['createdBy', 'participants.user'])
            ->where(function ($q) use ($userId) {
                $q->where('created_by_id', $userId)
                  ->orWhereHas('participants', function ($subQuery) use ($userId) {
                      $subQuery->where('user_id', $userId);
                  });
            });

        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->byWorkspace($filters['workspace_id']);
        }

        if (isset($filters['status']) && in_array($filters['status'], ['pending', 'accepted', 'declined', 'tentative'])) {
            $query->whereHas('participants', function ($subQuery) use ($userId, $filters) {
                $subQuery->where('user_id', $userId)
                         ->where('status', $filters['status']);
            });
        }

        return $query->orderBy('start_date', 'asc')->get();
    }

    /**
     * Create a new event
     */
    public function create(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            $event = Event::create($data);
            
            // Add participants if provided
            if (isset($data['participants']) && is_array($data['participants'])) {
                foreach ($data['participants'] as $userId) {
                    $event->participants()->create([
                        'user_id' => $userId,
                        'status' => 'pending'
                    ]);
                }
            }

            return $event->load(['createdBy', 'participants.user']);
        });
    }

    /**
     * Update an event
     */
    public function update(Event $event, array $data): Event
    {
        $event->update($data);
        return $event->fresh(['createdBy', 'participants.user']);
    }

    /**
     * Delete an event
     */
    public function delete(Event $event): bool
    {
        return $event->delete();
    }

    /**
     * Find event by ID with relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Event
    {
        $defaultRelations = ['createdBy', 'participants.user', 'workspace'];
        $relations = array_merge($defaultRelations, $relations);
        
        return Event::with($relations)->find($id);
    }

    /**
     * Get upcoming events
     */
    public function getUpcoming(int $workspaceId, int $limit = 10): Collection
    {
        return Event::with(['createdBy', 'participants.user'])
            ->byWorkspace($workspaceId)
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
    }
}