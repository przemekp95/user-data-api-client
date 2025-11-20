<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Services\UserDataService;
use App\Infrastructure\Api\GuzzleApiClient;
use App\Infrastructure\Cache\InMemoryCache;
use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Integration tests for UserDataService.
 * Tests real component interactions using actual implementations (not mocks).
 */
class UserDataServiceIntegrationTest extends TestCase
{
    private UserDataService $service;

    protected function setUp(): void
    {
        $apiClient = new GuzzleApiClient('https://jsonplaceholder.typicode.com', new NullLogger());
        $cache = new InMemoryCache();
        $this->service = new UserDataService($apiClient, $cache);
    }

    /**
     * Test end-to-end data flow: API → Service → Cache → Response
     */
    public function testEndToEndDataFlow(): void
    {
        $userId = 1;

        // Fetch data from real API
        $userData = $this->service->getUserData($userId);

        $this->assertInstanceOf(UserDataDTO::class, $userData);
        $this->assertEquals($userId, $userData->id);
        $this->assertIsString($userData->name);
        $this->assertIsString($userData->email);
        $this->assertIsString($userData->city);
        $this->assertIsString($userData->company);

        // Second call should be from cache
        $cachedData = $this->service->getUserData($userId);

        // Should return same data
        $this->assertEquals($userData, $cachedData);
    }

    /**
     * Test error handling with invalid API endpoint
     */
    public function testErrorHandlingWithInvalidEndpoint(): void
    {
        $invalidClient = new GuzzleApiClient('https://invalid-domain-test-123.com', new NullLogger());
        $service = new UserDataService($invalidClient, new InMemoryCache());

        $this->expectException(\Exception::class);
        $service->getUserData(1);
    }
}
