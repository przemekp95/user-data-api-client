<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Interfaces\ApiClientInterface;
use App\Application\Interfaces\CacheInterface;
use App\Domain\DTO\UserDataDTO;

/**
 * User data service following Single Responsibility Principle
 * Orchestrates API calls and caching to provide user data
 */
class UserDataService
{
    private const CACHE_KEY_PREFIX = 'user_data_';
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly ApiClientInterface $apiClient,
        private readonly CacheInterface $cache,
    ) {}

    /**
     * Get user data with caching
     * Follows the Don't Repeat Yourself principle with consistent cache key generation
     *
     * @param int $userId User ID to retrieve
     * @return UserDataDTO Processed user data
     * @throws \RuntimeException When API request fails or data is invalid
     */
    public function getUserData(int $userId): UserDataDTO
    {
        $cacheKey = $this->generateCacheKey($userId);

        // Try to get from cache first (Don't Repeat Yourself - consistent key usage)
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }

        // Fetch from API
        $apiData = $this->apiClient->fetchUserData($userId);

        // Process and transform data
        $processedData = $this->processApiResponse($apiData);

        // Cache the result (Don't Repeat Yourself - consistent TTL and key)
        $this->cache->set($cacheKey, $processedData, self::CACHE_TTL_SECONDS);

        return $processedData;
    }

    /**
     * Generate cache key following consistent naming convention
     * Following Don't Repeat Yourself principle
     */
    private function generateCacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . $userId;
    }

    /**
     * Process raw API response into our domain DTO
     * Following Single Responsibility Principle - data transformation logic
     */
    private function processApiResponse(array $apiData): UserDataDTO
    {
        $this->validateProcessedData($apiData);

        return new UserDataDTO(
            id: (int) $apiData['id'],
            name: (string) $apiData['name'],
            email: (string) $apiData['email'],
            city: (string) $apiData['address']['city'],
            company: (string) $apiData['company']['name'],
        );
    }

    /**
     * Validate that processed data structure meets our domain requirements
     * Following Fail Fast principle - validate early and clearly
     */
    private function validateProcessedData(array $apiData): void
    {
        if (!isset($apiData['id'], $apiData['name'], $apiData['email'])) {
            throw new \RuntimeException('API response missing required user data fields');
        }

        if (!isset($apiData['address']['city'])) {
            throw new \RuntimeException('API response missing address.city field');
        }

        if (!isset($apiData['company']['name'])) {
            throw new \RuntimeException('API response missing company.name field');
        }
    }
}
