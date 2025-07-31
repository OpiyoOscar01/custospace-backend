<?php

namespace App\Repositories\Contracts;

use App\Models\ApiToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface ApiTokenRepositoryInterface
 * 
 * Defines the contract for ApiToken repository operations
 */
interface ApiTokenRepositoryInterface
{
    /**
     * Get all API tokens with optional pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an API token by ID
     */
    public function findById(int $id): ?ApiToken;

    /**
     * Find an API token by token string
     */
    public function findByToken(string $token): ?ApiToken;

    /**
     * Create a new API token
     */
    public function create(array $data): ApiToken;

    /**
     * Update an existing API token
     */
    public function update(ApiToken $apiToken, array $data): bool;

    /**
     * Delete an API token
     */
    public function delete(ApiToken $apiToken): bool;

    /**
     * Get tokens by user ID
     */
    public function getByUserId(int $userId): Collection;

    /**
     * Get active tokens by user ID
     */
    public function getActiveByUserId(int $userId): Collection;

    /**
     * Revoke (delete) all tokens for a user
     */
    public function revokeAllByUserId(int $userId): bool;

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int;
}
