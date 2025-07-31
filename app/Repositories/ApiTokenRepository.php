<?php

namespace App\Repositories;

use App\Models\ApiToken;
use App\Repositories\Contracts\ApiTokenRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ApiTokenRepository
 * 
 * Handles data access operations for ApiToken model
 */
class ApiTokenRepository implements ApiTokenRepositoryInterface
{
    /**
     * Get all API tokens with optional pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return ApiToken::with('user')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find an API token by ID
     */
    public function findById(int $id): ?ApiToken
    {
        return ApiToken::with('user')->find($id);
    }

    /**
     * Find an API token by token string
     */
    public function findByToken(string $token): ?ApiToken
    {
        return ApiToken::where('token', $token)->first();
    }

    /**
     * Create a new API token
     */
    public function create(array $data): ApiToken
    {
        return ApiToken::create($data);
    }

    /**
     * Update an existing API token
     */
    public function update(ApiToken $apiToken, array $data): bool
    {
        return $apiToken->update($data);
    }

    /**
     * Delete an API token
     */
    public function delete(ApiToken $apiToken): bool
    {
        return $apiToken->delete();
    }

    /**
     * Get tokens by user ID
     */
    public function getByUserId(int $userId): Collection
    {
        return ApiToken::where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * Get active tokens by user ID
     */
    public function getActiveByUserId(int $userId): Collection
    {
        return ApiToken::where('user_id', $userId)
            ->active()
            ->latest()
            ->get();
    }

    /**
     * Revoke (delete) all tokens for a user
     */
    public function revokeAllByUserId(int $userId): bool
    {
        return ApiToken::where('user_id', $userId)->delete() > 0;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return ApiToken::expired()->delete();
    }
}
