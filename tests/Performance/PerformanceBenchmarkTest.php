<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Application\Services\UserDataService;
use App\Infrastructure\Api\GuzzleApiClient;
use App\Infrastructure\Cache\InMemoryCache;
use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Performance benchmark tests to measure system performance and identify bottlenecks.
 * Critical for production readiness and scalability assessment.
 */
class PerformanceBenchmarkTest extends TestCase
{
    private UserDataService $service;

    protected function setUp(): void
    {
        $apiClient = new GuzzleApiClient('https://jsonplaceholder.typicode.com', new NullLogger());
        $cache = new InMemoryCache();
        $this->service = new UserDataService($apiClient, $cache);
    }

    /**
     * Benchmark cold cache performance (first API hit)
     * Critical for measuring external API dependency latency.
     */
    public function testColdCacheApiPerformance(): void
    {
        $userId = 1;

        // Measure cold cache API call time
        $startTime = microtime(true);
        $userData = $this->service->getUserData($userId);
        $coldCacheTime = microtime(true) - $startTime;

        // Assert reasonable performance (< 500ms for cold API call)
        $this->assertLessThan(0.5, $coldCacheTime,
            "Cold API call took {$coldCacheTime}s, should be < 0.5s");

        $this->assertInstanceOf(UserDataDTO::class, $userData);
    }

    /**
     * Benchmark warm cache performance (cache hit)
     * Measures internal cache operation speed.
     */
    public function testWarmCachePerformance(): void
    {
        $userId = 1;

        // Warm up cache
        $this->service->getUserData($userId);

        // Measure warm cache performance
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $userData = $this->service->getUserData($userId);
        }
        $warmCacheTime = (microtime(true) - $startTime) / 100; // Average per call

        // Assert excellent cache performance (< 1ms for cache hits)
        $this->assertLessThan(0.001, $warmCacheTime,
            "Warm cache call average {$warmCacheTime}s, should be < 0.001s");

        $this->assertInstanceOf(UserDataDTO::class, $userData);
    }

    /**
     * Benchmark cache memory usage under load
     * Tests memory leak prevention in cache operations.
     */
    public function testCacheEfficiencyUnderLoad(): void
    {
        $userIds = range(1, 100); // Test with 100 different users

        // Measure memory before
        $startMemory = memory_get_usage(true);

        foreach ($userIds as $userId) {
            $userData = $this->service->getUserData($userId);
            $this->assertInstanceOf(UserDataDTO::class, $userData);
        }

        // Measure memory after
        $endMemory = memory_get_usage(true);
        $memoryIncrease = $endMemory - $startMemory;

        // Assert reasonable memory usage (< 50MB increase for 100 calls)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease,
            "Memory usage increased by {$memoryIncrease} bytes");
    }

    /**
     * Benchmark concurrent request simulation
     * Tests system performance under concurrent load.
     */
    public function testConcurrentLoadSimulation(): void
    {
        $concurrentUsers = 10;
        $iterations = 5;

        $startTime = microtime(true);

        // Simulate concurrent-like load
        for ($i = 0; $i < $iterations; $i++) {
            for ($userId = 1; $userId <= $concurrentUsers; $userId++) {
                $userData = $this->service->getUserData($userId);
                $this->assertInstanceOf(UserDataDTO::class, $userData);
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgTimePerRequest = $totalTime / ($concurrentUsers * $iterations);

        // Assert performance under concurrent load (< 100ms avg per request)
        $this->assertLessThan(0.1, $avgTimePerRequest,
            "Average concurrent request time: {$avgTimePerRequest}s");
    }

    /**
     * Benchmark service throughput (operations per second)
     */
    public function testServiceThroughput(): void
    {
        $operations = 50;
        $userId = 1;

        // Warm up cache
        $this->service->getUserData($userId);

        $startTime = microtime(true);

        // Run operations with warm cache
        for ($i = 0; $i < $operations; $i++) {
            $userData = $this->service->getUserData($userId);
            $this->assertInstanceOf(UserDataDTO::class, $userData);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $operationsPerSecond = $operations / $totalTime;

        // Assert good throughput (> 100 ops/second with cache)
        $this->assertGreaterThan(100, $operationsPerSecond,
            "Throughput: {$operationsPerSecond} ops/sec - too slow");
    }
}
