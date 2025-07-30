<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReminderRequest;
use App\Http\Requests\UpdateReminderRequest;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizationException;

/**
 * Reminder Controller
 * 
 * Handles API endpoints for reminder management
 */
class ReminderController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ReminderService $reminderService
    ) {
    }

    /**
     * Display a listing of reminders
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reminder::class);

        $perPage = $request->get('per_page', 15);
        $reminders = $this->reminderService->getPaginatedReminders($perPage);

        return ReminderResource::collection($reminders);
    }

    /**
     * Store a newly created reminder
     */
    public function store(CreateReminderRequest $request): ReminderResource
    {
        $this->authorize('create', Reminder::class);

        $reminder = $this->reminderService->createReminder($request->validated());

        return new ReminderResource($reminder);
    }

    /**
     * Display the specified reminder
     */
    public function show(Reminder $reminder): ReminderResource
    {
        $this->authorize('view', $reminder);

        $reminder = $this->reminderService->findReminder($reminder->id);

        return new ReminderResource($reminder);
    }

    /**
     * Update the specified reminder
     */
    public function update(UpdateReminderRequest $request, Reminder $reminder): ReminderResource
    {
        $this->authorize('update', $reminder);

        $this->reminderService->updateReminder($reminder, $request->validated());

        return new ReminderResource($reminder->fresh());
    }

    /**
     * Remove the specified reminder
     */
    public function destroy(Reminder $reminder): JsonResponse
    {
        $this->authorize('delete', $reminder);

        $this->reminderService->deleteReminder($reminder);

        return response()->json([
            'message' => 'Reminder deleted successfully'
        ]);
    }

    /**
     * Activate reminder (mark as not sent)
     */
    public function activate(Request $request, Reminder $reminder): JsonResponse
    {
        $this->authorize('update', $reminder);

        $request->validate([
            'remind_at' => ['nullable', 'date', 'after:now']
        ]);

        $success = $this->reminderService->activateReminder(
            $reminder,
            $request->get('remind_at')
        );

        return response()->json([
            'message' => $success ? 'Reminder activated successfully' : 'Failed to activate reminder',
            'data' => new ReminderResource($reminder->fresh())
        ]);
    }

    /**
     * Deactivate reminder (mark as sent)
     */
    public function deactivate(Reminder $reminder): JsonResponse
    {
        $this->authorize('update', $reminder);

        $success = $this->reminderService->deactivateReminder($reminder);

        return response()->json([
            'message' => $success ? 'Reminder deactivated successfully' : 'Failed to deactivate reminder',
            'data' => new ReminderResource($reminder->fresh())
        ]);
    }

    /**
     * Get user's reminders
     */
    public function userReminders(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $reminders = $this->reminderService->getUserReminders($userId);

        return ReminderResource::collection($reminders);
    }

    /**
     * Process pending reminders
     */
    public function processPending(): JsonResponse
    {
        $this->authorize('create', Reminder::class); // Admin only

        $processedReminders = $this->reminderService->processPendingReminders();

        return response()->json([
            'message' => 'Processed ' . $processedReminders->count() . ' pending reminders',
            'processed_count' => $processedReminders->count(),
            'data' => ReminderResource::collection($processedReminders)
        ]);
    }
}
