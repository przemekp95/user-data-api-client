<?php

declare(strict_types=1);

namespace App\Application\Interfaces;

/**
 * Interface for API client following Interface Segregation Principle
 * Only defines the method needed for user data fetching
 */
interface ApiClientInterface
{
    /**
     * Fetch user data from external API
     *
     * @param int $userId User ID to fetch
     * @return array Raw API response data
     *
     * @throws \RuntimeException When API request fails
     */
    public function fetchUserData(int $userId): array;
}
