<?php

namespace App\Repositories;

use App\Models\Reminder;
use App\Repositories\Contracts\ReminderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reminder Repository Implementation
 * 
 * Handles data access operations for reminders
 */
class ReminderRepository implements ReminderRepositoryInterface
{
    /**
     * Get all reminders with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Reminder::with(['user', 'remindable'])
            ->orderBy('remind_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find reminder by ID
     */
    public function findById(int $id): ?Reminder
    {
        return Reminder::with(['user', 'remindable'])->find($id);
    }

    /**
     * Create a new reminder
     */
    public function create(array $data): Reminder
    {
        return Reminder::create($data);
    }

    /**
     * Update existing reminder
     */
    public function update(Reminder $reminder, array $data): bool
    {
        return $reminder->update($data);
    }

    /**
     * Delete reminder
     */
    public function delete(Reminder $reminder): bool
    {
        return $reminder->delete();
    }

    /**
     * Get reminders for a specific user
     */
    public function getUserReminders(int $userId): Collection
    {
        return Reminder::with(['remindable'])
            ->where('user_id', $userId)
            ->orderBy('remind_at', 'desc')
            ->get();
    }

    /**
     * Get pending reminders due before specific datetime
     */
    public function getPendingRemindersDueBefore(string $datetime): Collection
    {
        return Reminder::with(['user', 'remindable'])
            ->pending()
            ->dueBefore($datetime)
            ->get();
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(Reminder $reminder): bool
    {
        return $reminder->markAsSent();
    }

    /**
     * Get reminders by type
     */
    public function getRemindersByType(string $type): Collection
    {
        return Reminder::with(['user', 'remindable'])
            ->where('type', $type)
            ->orderBy('remind_at', 'desc')
            ->get();
    }
}
