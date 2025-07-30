<?php

namespace App\Services;

use App\Models\Reminder;
use App\Repositories\Contracts\ReminderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reminder Service
 * 
 * Handles business logic for reminder operations
 */
class ReminderService
{
    public function __construct(
        private ReminderRepositoryInterface $reminderRepository
    ) {}

    /**
     * Get paginated reminders
     */
    public function getPaginatedReminders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->reminderRepository->paginate($perPage);
    }

    /**
     * Find reminder by ID
     */
    public function findReminder(int $id): ?Reminder
    {
        return $this->reminderRepository->findById($id);
    }

    /**
     * Create a new reminder
     */
    public function createReminder(array $data): Reminder
    {
        return $this->reminderRepository->create($data);
    }

    /**
     * Update existing reminder
     */
    public function updateReminder(Reminder $reminder, array $data): bool
    {
        return $this->reminderRepository->update($reminder, $data);
    }

    /**
     * Delete reminder
     */
    public function deleteReminder(Reminder $reminder): bool
    {
        return $this->reminderRepository->delete($reminder);
    }

    /**
     * Get user's reminders
     */
    public function getUserReminders(int $userId): Collection
    {
        return $this->reminderRepository->getUserReminders($userId);
    }

    /**
     * Process pending reminders (send notifications/emails)
     */
    public function processPendingReminders(): Collection
    {
        $pendingReminders = $this->reminderRepository->getPendingRemindersDueBefore(now());

        foreach ($pendingReminders as $reminder) {
            // Process the reminder based on type
            $this->processReminder($reminder);
            
            // Mark as sent
            $this->reminderRepository->markAsSent($reminder);
        }

        return $pendingReminders;
    }

    /**
     * Activate reminder (mark as not sent and update remind_at if needed)
     */
    public function activateReminder(Reminder $reminder, ?string $newRemindAt = null): bool
    {
        $data = ['is_sent' => false];
        
        if ($newRemindAt) {
            $data['remind_at'] = $newRemindAt;
        }

        return $this->reminderRepository->update($reminder, $data);
    }

    /**
     * Deactivate reminder (mark as sent)
     */
    public function deactivateReminder(Reminder $reminder): bool
    {
        return $this->reminderRepository->markAsSent($reminder);
    }

    /**
     * Process individual reminder based on type
     */
    private function processReminder(Reminder $reminder): void
    {
        switch ($reminder->type) {
            case 'email':
                // Send email notification
                // Mail::to($reminder->user)->send(new ReminderMail($reminder));
                break;
            case 'sms':
                // Send SMS notification
                // SMS service integration
                break;
            case 'in_app':
                // Create in-app notification
                // NotificationService::createInAppNotification($reminder);
                break;
        }
    }
}
