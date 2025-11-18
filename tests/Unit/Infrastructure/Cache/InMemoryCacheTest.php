<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cache;

use App\Infrastructure\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * Test Driven Development - comprehensive cache behavior tests
 * Tests TTL, expiration, and cache operations
 */
class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }

    public function testSetAndGetValue(): void
    {
        $key = 'test_key';
        $data = 'test_data';

        $this->cache->set($key, $data);
        $result = $this->cache->get($key);

        $this->assertEquals($data, $result);
    }

    public function testGetReturnsNullForNonexistentKey(): void
    {
        $result = $this->cache->get('nonexistent_key');

        $this->assertNull($result);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $key = 'existing_key';

        $this->cache->set($key, 'some_data');
        $result = $this->cache->has($key);

        $this->assertTrue($result);
    }

    public function testHasReturnsFalseForNonexistentKey(): void
    {
        $result = $this->cache->has('nonexistent_key');

        $this->assertFalse($result);
    }

    public function testDataExpiresAfterTtl(): void
    {
        $key = 'expiring_key';
        $ttl = 1; // 1 second

        $this->cache->set($key, 'data', $ttl);

        // Data should exist immediately
        $this->assertTrue($this->cache->has($key));
        $this->assertEquals('data', $this->cache->get($key));

        // Sleep for longer than TTL
        sleep(2);

        // Data should have expired
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    public function testDefaultTtlIsUsedWhenNotSpecified(): void
    {
        $key = 'default_ttl_key';

        $this->cache->set($key, 'data'); // No TTL specified, should use default 60 seconds

        // Still valid
        $this->assertTrue($this->cache->has($key));

        // Manually modify internal storage to expire instantly
        $reflection = new \ReflectionClass($this->cache);
        $storageProperty = $reflection->getProperty('storage');

        $storage = $storageProperty->getValue($this->cache);
        $storage[$key]['expires_at'] = time() - 1; // Expire in the past
        $storageProperty->setValue($this->cache, $storage);

        // Now expired
        $this->assertFalse($this->cache->has($key));
    }

    public function testDeleteExistingKeyReturnsTrue(): void
    {
        $key = 'to_delete';

        $this->cache->set($key, 'data');
        $result = $this->cache->delete($key);

        $this->assertTrue($result);
        $this->assertNull($this->cache->get($key));
    }

    public function testDeleteNonexistentKeyReturnsFalse(): void
    {
        $result = $this->cache->delete('nonexistent_key');

        $this->assertFalse($result);
    }

    public function testCleanExpiredRemovesExpiredEntries(): void
    {
        // Set data that will expire
        $this->cache->set('expired_key', 'expired_data', 1);
        $this->cache->set('valid_key', 'valid_data', 300); // Valid for 5 minutes

        // Sleep to let first entry expire
        sleep(2);

        // Get reflection to access private storage for verification
        $reflection = new \ReflectionClass($this->cache);
        $storageProperty = $reflection->getProperty('storage');

        // Before cleanExpired - both entries exist
        $storageBefore = $storageProperty->getValue($this->cache);
        $this->assertArrayHasKey('expired_key', $storageBefore);
        $this->assertArrayHasKey('valid_key', $storageBefore);

        // Clean expired entries
        $this->cache->cleanExpired();

        // After cleanExpired - only valid entry remains
        $storageAfter = $storageProperty->getValue($this->cache);
        $this->assertArrayNotHasKey('expired_key', $storageAfter);
        $this->assertArrayHasKey('valid_key', $storageAfter);
    }

    public function testMultipleUsersDontInterfere(): void
    {
        // Test caching behavior for different users (isolation)
        $this->cache->set('user_data_1', 'user1_data');
        $this->cache->set('user_data_2', 'user2_data');

        $this->assertEquals('user1_data', $this->cache->get('user_data_1'));
        $this->assertEquals('user2_data', $this->cache->get('user_data_2'));

        // Deleting one user doesn't affect the other
        $this->cache->delete('user_data_1');
        $this->assertNull($this->cache->get('user_data_1'));
        $this->assertEquals('user2_data', $this->cache->get('user_data_2'));
    }
}
