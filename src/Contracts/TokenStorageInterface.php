<?php

namespace Sumit\LaravelPayment\Contracts;

use Sumit\LaravelPayment\DTO\TokenData;

/**
 * Token Storage Interface
 * 
 * Defines the contract for token storage implementations.
 * Users can implement this interface to store tokens in their own way.
 */
interface TokenStorageInterface
{
    /**
     * Store a payment token
     *
     * @param TokenData $tokenData Token data
     * @param mixed $userId User identifier (can be any type)
     * @return mixed Returns whatever makes sense for the implementation
     */
    public function storeToken(TokenData $tokenData, mixed $userId): mixed;

    /**
     * Retrieve a payment token
     *
     * @param mixed $tokenId Token identifier
     * @param mixed $userId User identifier
     * @return TokenData|null Token data or null if not found
     */
    public function retrieveToken(mixed $tokenId, mixed $userId): ?TokenData;

    /**
     * Delete a payment token
     *
     * @param mixed $tokenId Token identifier
     * @param mixed $userId User identifier
     * @return bool True if deleted, false otherwise
     */
    public function deleteToken(mixed $tokenId, mixed $userId): bool;

    /**
     * Get all tokens for a user
     *
     * @param mixed $userId User identifier
     * @return array Array of TokenData objects
     */
    public function getUserTokens(mixed $userId): array;

    /**
     * Set a token as default
     *
     * @param mixed $tokenId Token identifier
     * @param mixed $userId User identifier
     * @return bool True if set as default, false otherwise
     */
    public function setDefaultToken(mixed $tokenId, mixed $userId): bool;
}
