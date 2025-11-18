<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services;

use App\Application\Interfaces\ApiClientInterface;
use App\Application\Interfaces\CacheInterface;
use App\Application\Services\UserDataService;
use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Test Driven Development - comprehensive tests for UserDataService
 * Tests caching behavior, error handling, and business logic.
 */
class UserDataServiceTest extends TestCase
{
    private ApiClientInterface $apiClient;

    private CacheInterface $cache;

    private UserDataService $service;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->service = new UserDataService($this->apiClient, $this->cache);
    }

    public function testReturnsCachedDataWhenAvailable(): void
    {
        $userId = 1;
        $expectedDto = new UserDataDTO(1, 'Leanne Graham', 'Sincere@april.biz', 'Gwenborough', 'Romaguera-Crona');

        // Mock cache to return data (Don't Repeat Yourself - same key structure)
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn($expectedDto);

        // API should not be called when data is cached
        $this->apiClient->expects($this->never())
            ->method('fetchUserData');

        $result = $this->service->getUserData($userId);

        $this->assertEquals($expectedDto, $result);
    }

    public function testFetchesFromApiWhenNotCached(): void
    {
        $userId = 1;
        $apiResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
            'address' => ['city' => 'Gwenborough'],
            'company' => ['name' => 'Romaguera-Crona']
        ];
        $expectedDto = new UserDataDTO(1, 'Leanne Graham', 'Sincere@april.biz', 'Gwenborough', 'Romaguera-Crona');

        // Mock cache miss
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null);

        // Mock API call
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(1)
            ->willReturn($apiResponse);

        // Mock cache set
        $this->cache->expects($this->once())
            ->method('set')
            ->with('user_data_1', $expectedDto, 60);

        $result = $this->service->getUserData($userId);

        $this->assertEquals($expectedDto, $result);
    }

    public function testThrowsExceptionWhenApiFails(): void
    {
        $userId = 1;

        // Mock cache miss
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null);

        // Mock API failure
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(1)
            ->willThrowException(new RuntimeException('API error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API error');

        $this->service->getUserData($userId);
    }

    public function testThrowsExceptionForInvalidApiResponse(): void
    {
        $userId = 1;
        $invalidApiResponse = ['invalid' => 'data']; // Missing required fields

        // Mock cache miss
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null);

        // Mock API returning invalid data
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(1)
            ->willReturn($invalidApiResponse);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API response missing required user data fields');

        $this->service->getUserData($userId);
    }

    public function testThrowsExceptionForInvalidApiResponseStructure(): void
    {
        $userId = 1;
        $invalidApiResponse = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'address' => ['invalid_city' => 'City'], // Missing 'city' key
            'company' => 'Invalid Company' // Should be array
        ];

        // Mock cache miss
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null);

        // Mock API returning invalid data structure
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(1)
            ->willReturn($invalidApiResponse);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API response missing address.city field');

        $this->service->getUserData($userId);
    }

    public function testConsistentCacheKeyGeneration(): void
    {
        // Test that same user ID always generates same cache key (DRY principle)
        $userId = 42;

        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_42')
            ->willReturn(null);

        $apiResponse = [
            'id' => 42,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'address' => ['city' => 'Test City'],
            'company' => ['name' => 'Test Company']
        ];

        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(42)
            ->willReturn($apiResponse);

        $this->cache->expects($this->once())
            ->method('set')
            ->with('user_data_42', $this->anything(), 60);

        $this->service->getUserData($userId);
    }
}
