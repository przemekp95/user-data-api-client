<?php

declare(strict_types=1);

namespace App\Application\Interfaces;

/**
 * Interface for caching mechanism following Single Responsibility Principle
 * Only handles caching operations without business logic
 */
interface CacheInterface
{
    /**
     * Get cached data by key
     *
     * @param string $key Cache key
     * @return mixed Cached data or null if not found/expired
     */
    public function get(string $key): mixed;

    /**
     * Store data in cache
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds (optional, null for default)
     * @return bool True on success
     */
    public function set(string $key, mixed $data, ?int $ttl = null): bool;

    /**
     * Check if key exists in cache and is not expired
     *
     * @param string $key Cache key
     * @return bool True if key exists and valid
     */
    public function has(string $key): bool;

    /**
     * Delete cached data by key
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public function delete(string $key): bool;
}
