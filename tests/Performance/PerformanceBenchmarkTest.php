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
 * Each test configures its own mocks to ensure controlled, predictable measurements.
 */
class PerformanceBenchmarkTest extends TestCase
{
    private UserDataService $service;
    /** @var \PHPUnit\Framework\MockObject\MockObject&ApiClientInterface */
    private ApiClientInterface $apiClient;
    /** @var \PHPUnit\Framework\MockObject\MockObject&CacheInterface */
    private CacheInterface $cache;

    /**
     * Clean setup without fixed expectations.
     * Each test overrides mock behavior as needed.
     */
    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->service = new UserDataService($this->apiClient, $this->cache);
    }

    /**
     * Benchmark warm cache performance with realistic cache operations
     */
    public function testWarmCachePerformanceWithRealisticBehavior(): void
    {
        $userId = 1;
        $expectedDto = new UserDataDTO(1, 'Test User', 'test@example.com', 'Test City', 'Test Company');

        // Configure cache to return data (warm cache scenario)
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
        $cacheTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        $this->assertEquals($expectedDto, $result);

        // Cache should be reasonably fast (allow for PHP overhead)
        $this->assertLessThan(15.0, $cacheTime,
            "Cache retrieval took {$cacheTime}ms - significantly slower than expected");
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

        // Configure cache miss for this specific test
        $this->cache->expects($this->once())
            ->method('get')
            ->with('user_data_2')
            ->willReturn(null);

        // Configure API to return specific data for userId 2
        $this->apiClient->expects($this->once())
            ->method('fetchUserData')
            ->with(2)
            ->willReturn($apiResponse);

        // Configure cache to store the result
        $this->cache->expects($this->once())
            ->method('set')
            ->with('user_data_2', $expectedDto, 60);

        $startTime = microtime(true);
        $result = $this->service->getUserData($userId);
        $fullOperationTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals($expectedDto, $result);
        $this->assertInstanceOf(UserDataDTO::class, $result); // Explicit assertion for this test

        // Full operation should complete reasonably
        $this->assertLessThan(100.0, $fullOperationTime);
    }

    /**
     * Test cache operation frequency under load
     */
    public function testCacheOperationFrequencyUnderLoad(): void
    {
        // Focus on performance, simplify mock setup to avoid PHPUnit compatibility issues
        $userId = 1;
        $numCalls = 5;

        // Setup cache and API mocks
        $this->cache->expects($this->exactly($numCalls))
            ->method('get')
            ->willReturn(null);

        $apiResponse = [
            'id' => 1,
            'name' => 'Load Test User',
            'email' => 'load@example.com',
            'address' => ['city' => 'Load City'],
            'company' => ['name' => 'Load Company']
        ];

        $this->apiClient->expects($this->exactly($numCalls))
            ->method('fetchUserData')
            ->willReturn($apiResponse);

        $this->cache->expects($this->exactly($numCalls))
            ->method('set')
            ->with($this->stringContains('user_data_'), $this->anything(), 60);

        $startTime = microtime(true);
        for ($i = 0; $i < $numCalls; $i++) {
            $result = $this->service->getUserData($userId);
            $this->assertInstanceOf(UserDataDTO::class, $result);
        }
        $totalTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(50.0, $totalTime);

        // This test focuses on performance under load with simplified but valid assertions
    }

    /**
     * Benchmark service response consistency
     */
    public function testServiceResponseConsistency(): void
    {
        $userId = 1;
        $expectedApiResponse = [
            'id' => 1,
            'name' => 'Consistent User',
            'email' => 'consistent@example.com',
            'address' => ['city' => 'Consistent City'],
            'company' => ['name' => 'Consistent Company']
        ];

        // Configure all calls to return same data
        $this->cache->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->apiClient->expects($this->any())
            ->method('fetchUserData')
            ->willReturn($expectedApiResponse);

        $this->cache->expects($this->any())
            ->method('set');

        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->service->getUserData($userId);
        }

        // All results should be identical, and test performs assertions
        $firstResult = $results[0];
        foreach ($results as $result) {
            $this->assertEquals($firstResult, $result);
            $this->assertInstanceOf(UserDataDTO::class, $result);
        }
    }
}
