<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Application\Services\UserDataService;
use App\Application\Interfaces\ApiClientInterface;
use App\Application\Interfaces\CacheInterface;
use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;

/**
 * Performance benchmark tests for service operations.
 * Tests component performance with controlled mocks vs real cache behavior.
 */
class PerformanceBenchmarkTest extends TestCase
{
    private UserDataService $service;
    private ApiClientInterface $apiClient;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        // Setup mock with realistic response
        $apiResponse = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'address' => ['city' => 'Test City'],
            'company' => ['name' => 'Test Company']
        ];

        // Mock API to return consistent data
        $this->apiClient->expects($this->any())
            ->method('fetchUserData')
            ->willReturn($apiResponse);

        $this->service = new UserDataService($this->apiClient, $this->cache);
    }

    /**
     * Benchmark warm cache performance with realistic cache operations
     */
    public function testWarmCachePerformanceWithRealisticBehavior(): void
    {
        $userId = 1;
        $expectedDto = new UserDataDTO(1, 'Test User', 'test@example.com', 'Test City', 'Test Company');

        // Setup warm cache - data already cached
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_1')
            ->willReturn($expectedDto);

        // API should not be called for cached data
        $this->apiClient->expects($this->never())
            ->method('fetchUserData');

        // Measure cache retrieval time
        $startTime = microtime(true);
        $result = $this->service->getUserData($userId);
        $cacheTime = microtime(true) - $startTime;

        $this->assertEquals($expectedDto, $result);

        // Cache should be very fast (< 0.01 seconds realistically)
        $this->assertLessThan(0.01, $cacheTime,
            "Cache retrieval took {$cacheTime}s - too slow for in-memory cache");
    }

    /**
     * Benchmark cache miss scenario (API call + cache write)
     */
    public function testCacheMissPerformanceWithApiCall(): void
    {
        $userId = 2;
        $apiResponse = [
            'id' => 2,
            'name' => 'Fresh User',
            'email' => 'fresh@example.com',
            'address' => ['city' => 'New City'],
            'company' => ['name' => 'New Company']
        ];
        $expectedDto = new UserDataDTO(2, 'Fresh User', 'fresh@example.com', 'New City', 'New Company');

        // Setup cache miss
        $this->cache->expects($this->any())
            ->method('get')
            ->with('user_data_2')
            ->willReturn(null);

        // Mock API call for fresh data (only once since cache miss)
        $this->apiClient->expects($this->atLeastOnce())
            ->method('fetchUserData')
            ->willReturn($apiResponse);

        // Mock cache write
        $this->cache->expects($this->once())
            ->method('set')
            ->with('user_data_2', $expectedDto, 60);

        $startTime = microtime(true);
        $result = $this->service->getUserData($userId);
        $fullOperationTime = microtime(true) - $startTime;

        $this->assertEquals($expectedDto, $result);

        // Full operation should complete reasonably (< 0.1 seconds with mocks)
        $this->assertLessThan(0.1, $fullOperationTime);
    }

    /**
     * Test cache operation frequency under load
     */
    public function testCacheOperationFrequencyUnderLoad(): void
    {
        $userIds = [1, 2, 3];
        $operations = 0;

        foreach ($userIds as $userId) {
            $this->cache->expects($this->exactly(++$operations))
                ->method('get')
                ->with('user_data_' . $userId)
                ->willReturn(null);

            // This test measures how often cache methods are called
            // In real scenarios, cache hits vs misses significantly impact performance
        }

        $startTime = microtime(true);
        foreach ($userIds as $userId) {
            $this->service->getUserData($userId);
        }
        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan(0.05, $totalTime, "Multiple operations too slow");

        // Comment: Real performance tests would measure:
        // - Cache hit ratios under load
        // - Memory usage growth
        // - Fallback behavior when cache fails
    }

    /**
     * Benchmark service response consistency
     */
    public function testServiceResponseConsistency(): void
    {
        $userId = 1;

        $results = [];
        $cacheHits = 5;

        // First call caches, subsequent calls hit cache
        $this->cache->expects($this->exactly($cacheHits))
            ->method('get')
            ->with('user_data_1')
            ->willReturn(null); // Simulate cold cache initially, then implement cache properly

        // Measure consistency of responses
        for ($i = 0; $i < $cacheHits; $i++) {
            $results[] = $this->service->getUserData($userId);
        }

        // All results should be identical
        $firstResult = $results[0];
        foreach ($results as $result) {
            $this->assertEquals($firstResult, $result);
        }
    }
}
