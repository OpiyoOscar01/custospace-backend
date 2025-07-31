<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserPreferenceRequest;
use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;
use App\Models\UserPreference;
use App\Services\UserPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class UserPreferenceController
 * 
 * Handles HTTP requests for UserPreference operations
 */
class UserPreferenceController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private UserPreferenceService $userPreferenceService
    ) {
        $this->authorizeResource(UserPreference::class, 'user_preference');
    }

    /**
     * Display a listing of user preferences
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $preferences = $this->userPreferenceService->getPaginatedPreferences($perPage);

        return UserPreferenceResource::collection($preferences);
    }

    /**
     * Store a newly created user preference
     */
    public function store(CreateUserPreferenceRequest $request): JsonResponse
    {
        $preference = $this->userPreferenceService->createPreference($request->validated());

        return response()->json([
            'message' => 'User preference created successfully.',
            'data' => new UserPreferenceResource($preference),
        ], 201);
    }

    /**
     * Display the specified user preference
     */
    public function show(UserPreference $userPreference): JsonResponse
    {
        return response()->json([
            'data' => new UserPreferenceResource($userPreference->load('user')),
        ]);
    }

    /**
     * Update the specified user preference
     */
    public function update(UpdateUserPreferenceRequest $request, UserPreference $userPreference): JsonResponse
    {
        $this->userPreferenceService->updatePreference($userPreference, $request->validated());

        return response()->json([
            'message' => 'User preference updated successfully.',
            'data' => new UserPreferenceResource($userPreference->fresh('user')),
        ]);
    }

    /**
     * Remove the specified user preference
     */
    public function destroy(UserPreference $userPreference): JsonResponse
    {
        $this->userPreferenceService->deletePreference($userPreference);

        return response()->json([
            'message' => 'User preference deleted successfully.',
        ]);
    }

    /**
     * Get all preferences for a specific user
     */
    public function getUserPreferences(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewAny', UserPreference::class);
        
        $preferences = $this->userPreferenceService->getUserPreferences($userId);

        return response()->json([
            'data' => UserPreferenceResource::collection($preferences),
        ]);
    }

    /**
     * Set a preference for a user (create or update)
     */
    public function setUserPreference(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string',
        ]);

        $this->authorize('create', UserPreference::class);

        $preference = $this->userPreferenceService->setUserPreference(
            $userId,
            $request->key,
            $request->value
        );

        return response()->json([
            'message' => 'User preference set successfully.',
            'data' => new UserPreferenceResource($preference),
        ]);
    }

    /**
     * Bulk set preferences for a user
     */
    public function bulkSetUserPreferences(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'required|string',
        ]);

        $this->authorize('create', UserPreference::class);

        $preferences = $this->userPreferenceService->setUserPreferences(
            $userId,
            $request->preferences
        );

        return response()->json([
            'message' => 'User preferences set successfully.',
            'data' => UserPreferenceResource::collection($preferences),
        ]);
    }
}
