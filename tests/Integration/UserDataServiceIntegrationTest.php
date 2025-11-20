<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Interfaces\ApiClientInterface;
use App\Application\Interfaces\CacheInterface;
use App\Application\Services\UserDataService;
use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for UserDataService isolating business logic.
 * Uses controlled mocks for external dependencies but tests real service integration.
 *
 * Tests the interaction between Service + Cache while mocking external API calls.
 */
class UserDataServiceIntegrationTest extends TestCase
{
    private UserDataService $service;

    /** @var \PHPUnit\Framework\MockObject\MockObject&ApiClientInterface */
    private ApiClientInterface $apiClient;

    /** @var \PHPUnit\Framework\MockObject\MockObject&CacheInterface */
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->service = new UserDataService($this->apiClient, $this->cache);
    }

    /**
     * Test service fetches from cache when available.
     */
    public function testServiceFetchesFromCacheWhenAvailable(): void
    {
        $userId = 1;
        $expectedDto = new UserDataDTO(1, 'John Doe', 'john@example.com', 'New York', 'Tech Corp');

        // Cache returns data
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn($expectedDto);

        // API should not be called
        $this->apiClient->expects($this->never())
            ->method('fetchUserData');

        $result = $this->service->getUserData($userId);

        $this->assertEquals($expectedDto, $result);
    }

    /**
     * Test service fetches from API and caches result when cache miss.
     */
    public function testServiceFetchesFromApiAndCachesOnCacheMiss(): void
    {
        $userId = 1;
        $apiResponse = [
            'id' => 1,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'address' => ['city' => 'London'],
            'company' => ['name' => 'London Corp']
        ];
        $expectedDto = new UserDataDTO(1, 'Jane Smith', 'jane@example.com', 'London', 'London Corp');

        // Cache miss
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null);

        // API returns data
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(1)
            ->willReturn($apiResponse);

        // Cache the result
        $this->cache->expects($this->once())
            ->method('set')
            ->with('user_data_1', $expectedDto, 60);

        $result = $this->service->getUserData($userId);

        $this->assertEquals($expectedDto, $result);
    }

    /**
     * Test error propagation through service layers.
     */
    public function testErrorPropagationToService(): void
    {
        $userId = 1;

        // Cache miss
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null);

        // API fails
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(1)
            ->willThrowException(new \RuntimeException('API failure'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API failure');

        $this->service->getUserData($userId);
    }

    /**
     * Test data transformation validation.
     */
    public function testDataTransformationIsValid(): void
    {
        $userId = 1;
        $apiResponse = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'address' => ['city' => 'Test City'],
            'company' => ['name' => 'Test Company']
        ];
        $expectedDto = new UserDataDTO(1, 'Test User', 'test@example.com', 'Test City', 'Test Company');

        // Setup mocks
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->willReturn($apiResponse);

        $this->cache->expects($this->once())
            ->method('set')
            ->with('user_data_1', $expectedDto, 60);

        $result = $this->service->getUserData($userId);

        $this->assertInstanceOf(UserDataDTO::class, $result);
        $this->assertEquals($expectedDto, $result);

        // Validate all fields are properly transferred
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('Test City', $result->city);
        $this->assertEquals('Test Company', $result->company);
    }
}
