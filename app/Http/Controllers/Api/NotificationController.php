<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Notification Controller
 * 
 * Handles API endpoints for notification management
 */
class NotificationController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Display a listing of notifications
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Notification::class);

        $perPage = $request->get('per_page', 15);
        $notifications = $this->notificationService->getPaginatedNotifications($perPage);

        return NotificationResource::collection($notifications);
    }

    /**
     * Store a newly created notification
     */
    public function store(CreateNotificationRequest $request): NotificationResource
    {
        $this->authorize('create', Notification::class);

        $notification = $this->notificationService->createNotification($request->validated());

        return new NotificationResource($notification);
    }

    /**
     * Display the specified notification
     */
    public function show(Notification $notification): NotificationResource
    {
        $this->authorize('view', $notification);

        $notification = $this->notificationService->findNotification($notification->id);

        return new NotificationResource($notification);
    }

    /**
     * Update the specified notification
     */
    public function update(UpdateNotificationRequest $request, Notification $notification): NotificationResource
    {
        $this->authorize('update', $notification);

        $this->notificationService->updateNotification($notification, $request->validated());

        return new NotificationResource($notification->fresh());
    }

    /**
     * Remove the specified notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->deleteNotification($notification);

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $success = $this->notificationService->markAsRead($notification);

        return response()->json([
            'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read',
            'data' => new NotificationResource($notification->fresh())
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $success = $this->notificationService->markAsUnread($notification);

        return response()->json([
            'message' => $success ? 'Notification marked as unread' : 'Failed to mark notification as unread',
            'data' => new NotificationResource($notification->fresh())
        ]);
    }

    /**
     * Get user's notifications
     */
    public function userNotifications(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getUserNotifications($userId);

        return NotificationResource::collection($notifications);
    }

    /**
     * Get user's unread notifications
     */
    public function unreadNotifications(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getUnreadNotifications($userId);

        return NotificationResource::collection($notifications);
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $success = $this->notificationService->markAllAsRead($userId);

        return response()->json([
            'message' => $success ? 'All notifications marked as read' : 'Failed to mark all notifications as read'
        ]);
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->notificationService->getUnreadCount($userId);

        return response()->json([
            'unread_count' => $count
        ]);
    }
}
