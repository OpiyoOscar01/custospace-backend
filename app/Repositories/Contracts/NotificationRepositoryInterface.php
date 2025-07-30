<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Notification Repository Interface
 * 
 * Defines contract for notification data access operations
 */
interface NotificationRepositoryInterface
{
    /**
     * Get all notifications with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find notification by ID
     */
    public function findById(int $id): ?Notification;

    /**
     * Create a new notification
     */
    public function create(array $data): Notification;

    /**
     * Update existing notification
     */
    public function update(Notification $notification, array $data): bool;

    /**
     * Delete notification
     */
    public function delete(Notification $notification): bool;

    /**
     * Get notifications for a specific user
     */
    public function getUserNotifications(int $userId): Collection;

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(int $userId): Collection;

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool;

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Notification $notification): bool;

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(int $userId): bool;

    /**
     * Get notifications by type
     */
    public function getNotificationsByType(string $type): Collection;
}
