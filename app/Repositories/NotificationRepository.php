<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Notification Repository Implementation
 * 
 * Handles data access operations for notifications
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Get all notifications with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Notification::with(['user', 'notifiable'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find notification by ID
     */
    public function findById(int $id): ?Notification
    {
        return Notification::with(['user', 'notifiable'])->find($id);
    }

    /**
     * Create a new notification
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Update existing notification
     */
    public function update(Notification $notification, array $data): bool
    {
        return $notification->update($data);
    }

    /**
     * Delete notification
     */
    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Get notifications for a specific user
     */
    public function getUserNotifications(int $userId): Collection
    {
        return Notification::with(['notifiable'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(int $userId): Collection
    {
        return Notification::with(['notifiable'])
            ->where('user_id', $userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead();
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Notification $notification): bool
    {
        return $notification->markAsUnread();
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(int $userId): bool
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get notifications by type
     */
    public function getNotificationsByType(string $type): Collection
    {
        return Notification::with(['user', 'notifiable'])
            ->ofType($type)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
