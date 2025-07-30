<?php

namespace App\Services;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Notification Service
 * 
 * Handles business logic for notification operations
 */
class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * Get paginated notifications
     */
    public function getPaginatedNotifications(int $perPage = 15): LengthAwarePaginator
    {
        return $this->notificationRepository->paginate($perPage);
    }

    /**
     * Find notification by ID
     */
    public function findNotification(int $id): ?Notification
    {
        return $this->notificationRepository->findById($id);
    }

    /**
     * Create a new notification
     */
    public function createNotification(array $data): Notification
    {
        return $this->notificationRepository->create($data);
    }

    /**
     * Update existing notification
     */
    public function updateNotification(Notification $notification, array $data): bool
    {
        return $this->notificationRepository->update($notification, $data);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(Notification $notification): bool
    {
        return $this->notificationRepository->delete($notification);
    }

    /**
     * Get user's notifications
     */
    public function getUserNotifications(int $userId): Collection
    {
        return $this->notificationRepository->getUserNotifications($userId);
    }

    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications(int $userId): Collection
    {
        return $this->notificationRepository->getUnreadNotifications($userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        return $this->notificationRepository->markAsRead($notification);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Notification $notification): bool
    {
        return $this->notificationRepository->markAsUnread($notification);
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }

    /**
     * Get notification count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->getUnreadNotifications($userId)->count();
    }

    /**
     * Create system notification
     */
    public function createSystemNotification(
        int $userId,
        string $title,
        string $message,
        string $type = 'system',
        ?array $data = null,
        ?string $notifiableType = null,
        ?int $notifiableId = null
    ): Notification {
        return $this->createNotification([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'notifiable_type' => $notifiableType ?? 'App\Models\System',
            'notifiable_id' => $notifiableId ?? 1,
        ]);
    }
}
