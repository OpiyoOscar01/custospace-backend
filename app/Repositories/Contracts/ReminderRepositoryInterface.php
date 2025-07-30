<?php

namespace App\Repositories\Contracts;

use App\Models\Reminder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reminder Repository Interface
 * 
 * Defines contract for reminder data access operations
 */
interface ReminderRepositoryInterface
{
    /**
     * Get all reminders with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find reminder by ID
     */
    public function findById(int $id): ?Reminder;

    /**
     * Create a new reminder
     */
    public function create(array $data): Reminder;

    /**
     * Update existing reminder
     */
    public function update(Reminder $reminder, array $data): bool;

    /**
     * Delete reminder
     */
    public function delete(Reminder $reminder): bool;

    /**
     * Get reminders for a specific user
     */
    public function getUserReminders(int $userId): Collection;

    /**
     * Get pending reminders due before specific datetime
     */
    public function getPendingRemindersDueBefore(string $datetime): Collection;

    /**
     * Mark reminder as sent
     */
    public function markAsSent(Reminder $reminder): bool;

    /**
     * Get reminders by type
     */
    public function getRemindersByType(string $type): Collection;
}
