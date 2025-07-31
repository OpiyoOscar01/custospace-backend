<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Repositories\Contracts\ApiTokenRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Class ApiTokenService
 * 
 * Handles business logic for ApiToken operations
 */
class ApiTokenService
{
    public function __construct(
        private ApiTokenRepositoryInterface $apiTokenRepository
    ) {}

    /**
     * Get paginated list of API tokens
     */
    public function getPaginatedTokens(int $perPage = 15): LengthAwarePaginator
    {
        return $this->apiTokenRepository->getAllPaginated($perPage);
    }

    /**
     * Find an API token by ID
     */
    public function findTokenById(int $id): ?ApiToken
    {
        return $this->apiTokenRepository->findById($id);
    }

    /**
     * Find an API token by token string
     */
    public function findTokenByString(string $token): ?ApiToken
    {
        return $this->apiTokenRepository->findByToken($token);
    }

    /**
     * Create a new API token
     */
    public function createToken(array $data): ApiToken
    {
        // Generate unique token if not provided
        if (!isset($data['token'])) {
            $data['token'] = $this->generateUniqueToken();
        }

        return $this->apiTokenRepository->create($data);
    }

    /**
     * Update an existing API token
     */
    public function updateToken(ApiToken $apiToken, array $data): bool
    {
        return $this->apiTokenRepository->update($apiToken, $data);
    }

    /**
     * Delete an API token
     */
    public function deleteToken(ApiToken $apiToken): bool
    {
        return $this->apiTokenRepository->delete($apiToken);
    }

    /**
     * Get all tokens for a specific user
     */
    public function getUserTokens(int $userId): Collection
    {
        return $this->apiTokenRepository->getByUserId($userId);
    }

    /**
     * Get active tokens for a specific user
     */
    public function getUserActiveTokens(int $userId): Collection
    {
        return $this->apiTokenRepository->getActiveByUserId($userId);
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(ApiToken $apiToken): bool
    {
        return $this->deleteToken($apiToken);
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        return $this->apiTokenRepository->revokeAllByUserId($userId);
    }

    /**
     * Mark a token as used
     */
    public function markTokenAsUsed(ApiToken $apiToken): void
    {
        $apiToken->markAsUsed();
    }

    /**
     * Check if a token is valid and active
     */
    public function isTokenValid(string $token): bool
    {
        $apiToken = $this->findTokenByString($token);
        
        return $apiToken && $apiToken->isActive();
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->apiTokenRepository->cleanupExpiredTokens();
    }

    /**
     * Generate a unique token
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(80);
        } while ($this->findTokenByString($token));

        return $token;
    }
}
