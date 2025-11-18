<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Interfaces\CacheInterface;

/**
 * Simple in-memory cache implementation
 * Following Single Responsibility Principle - only handles caching operations.
 */
class InMemoryCache implements CacheInterface
{
    private const DEFAULT_TTL = 60; // 60 seconds

    /** @var array<string, array{data: mixed, expires_at: int}> */
    private array $storage = [];

    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            return null;
        }

        return $this->storage[$key]['data'];
    }

    public function set(string $key, mixed $data, ?int $ttl = null): bool
    {
        $expiresAt = time() + ($ttl ?? self::DEFAULT_TTL);

        $this->storage[$key] = [
            'data' => $data,
            'expires_at' => $expiresAt
        ];

        return true;
    }

    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->storage)) {
            return false;
        }

        return $this->storage[$key]['expires_at'] > time();
    }

    public function delete(string $key): bool
    {
        if (!array_key_exists($key, $this->storage)) {
            return false;
        }

        unset($this->storage[$key]);
        return true;
    }

    /**
     * Clean expired cache entries (for garbage collection)
     * This method can be called periodically to free memory.
     */
    public function cleanExpired(): void
    {
        $currentTime = time();

        foreach ($this->storage as $key => $cacheItem) {
            if ($cacheItem['expires_at'] <= $currentTime) {
                unset($this->storage[$key]);
            }
        }
    }
}
