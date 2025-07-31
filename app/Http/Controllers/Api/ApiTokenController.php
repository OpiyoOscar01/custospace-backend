<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateApiTokenRequest;
use App\Http\Requests\UpdateApiTokenRequest;
use App\Http\Resources\ApiTokenResource;
use App\Models\ApiToken;
use App\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;;

/**
 * Class ApiTokenController
 * 
 * Handles HTTP requests for ApiToken operations
 */
class ApiTokenController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ApiTokenService $apiTokenService
    ) {
        $this->authorizeResource(ApiToken::class, 'api_token');
    }

    /**
     * Display a listing of API tokens
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $tokens = $this->apiTokenService->getPaginatedTokens($perPage);

        return ApiTokenResource::collection($tokens);
    }

    /**
     * Store a newly created API token
     */
    public function store(CreateApiTokenRequest $request): JsonResponse
    {
        $token = $this->apiTokenService->createToken($request->validated());

        return response()->json([
            'message' => 'API token created successfully.',
            'data' => new ApiTokenResource($token),
        ], 201);
    }

    /**
     * Display the specified API token
     */
    public function show(ApiToken $apiToken): JsonResponse
    {
        return response()->json([
            'data' => new ApiTokenResource($apiToken->load('user')),
        ]);
    }

    /**
     * Update the specified API token
     */
    public function update(UpdateApiTokenRequest $request, ApiToken $apiToken): JsonResponse
    {
        $this->apiTokenService->updateToken($apiToken, $request->validated());

        return response()->json([
            'message' => 'API token updated successfully.',
            'data' => new ApiTokenResource($apiToken->fresh('user')),
        ]);
    }

    /**
     * Remove the specified API token
     */
    public function destroy(ApiToken $apiToken): JsonResponse
    {
        $this->apiTokenService->deleteToken($apiToken);

        return response()->json([
            'message' => 'API token deleted successfully.',
        ]);
    }

    /**
     * Get all tokens for a specific user
     */
    public function getUserTokens(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewAny', ApiToken::class);
        
        $tokens = $this->apiTokenService->getUserTokens($userId);

        return response()->json([
            'data' => ApiTokenResource::collection($tokens),
        ]);
    }

    /**
     * Get active tokens for a specific user
     */
    public function getUserActiveTokens(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewAny', ApiToken::class);
        
        $tokens = $this->apiTokenService->getUserActiveTokens($userId);

        return response()->json([
            'data' => ApiTokenResource::collection($tokens),
        ]);
    }

    /**
     * Revoke a specific token
     */
    public function revoke(ApiToken $apiToken): JsonResponse
    {
        $this->authorize('delete', $apiToken);
        
        $this->apiTokenService->revokeToken($apiToken);

        return response()->json([
            'message' => 'API token revoked successfully.',
        ]);
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAll(Request $request, int $userId): JsonResponse
    {
        $this->authorize('delete', ApiToken::class);
        
        $count = $this->apiTokenService->revokeAllUserTokens($userId);

        return response()->json([
            'message' => "All API tokens for user revoked successfully. {$count} tokens were revoked.",
        ]);
    }

    /**
     * Clean up expired tokens
     */
    public function cleanup(): JsonResponse
    {
        $this->authorize('delete', ApiToken::class);
        
        $count = $this->apiTokenService->cleanupExpiredTokens();

        return response()->json([
            'message' => "Expired tokens cleaned up successfully. {$count} tokens were removed.",
        ]);
    }
}
