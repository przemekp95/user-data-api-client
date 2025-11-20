<?php

declare(strict_types=1);

namespace Tests\Contract;

use PHPUnit\Framework\TestCase;

/**
 * API Contract Tests - validate external API behavior and responses.
 * Ensures backward compatibility and schema stability of external dependencies.
 *
 * These tests act as an early warning system for changes in external APIs.
 */
class ApiContractTest extends TestCase
{
    private const JSONPLACEHOLDER_API = 'https://jsonplaceholder.typicode.com';

    /**
     * Test that external API response structure matches our expectations.
     * This is critical for data transformation reliability.
     */
    public function testApiSchemaCompliance(): void
    {
        $response = $this->fetchUserDataFromApi(1);

        $this->assertIsArray($response);

        // Required fields for our UserDataDTO
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('address', $response);
        $this->assertArrayHasKey('company', $response);

        //Nested structure validation
        $this->assertIsArray($response['address']);
        $this->assertArrayHasKey('city', $response['address']);

        $this->assertIsArray($response['company']);
        $this->assertArrayHasKey('name', $response['company']);
    }

    /**
     * Test API data types match our expectations.
     */
    public function testApiDataTypes(): void
    {
        $response = $this->fetchUserDataFromApi(1);

        // Validate data types
        $this->assertIsInt($response['id']);
        $this->assertIsString($response['name']);
        $this->assertIsString($response['email']);
        $this->assertIsString($response['address']['city']);
        $this->assertIsString($response['company']['name']);

        // Validate email format
        $this->assertStringContainsString('@', $response['email']);
        $this->assertGreaterThan(0, $response['id']);
    }

    /**
     * Test multiple users to ensure consistent response structure.
     */
    public function testMultipleUsersConsistency(): void
    {
        $userIds = [1, 2, 3];

        foreach ($userIds as $userId) {
            $response = $this->fetchUserDataFromApi($userId);
            $this->assertIsArray($response);

            // Validate each user has consistent structure
            $this->assertEquals($userId, $response['id']);
            $this->assertArrayHasKey('name', $response);
            $this->assertArrayHasKey('email', $response);
            $this->assertArrayHasKey('address', $response);
            $this->assertArrayHasKey('company', $response);

            // Validate nested structures
            $this->assertIsArray($response['address']);
            $this->assertArrayHasKey('city', $response['address']);
            $this->assertIsArray($response['company']);
            $this->assertArrayHasKey('name', $response['company']);
        }
    }

    /**
     * Test error responses for non-existent users.
     */
    public function testApiErrorResponses(): void
    {
        // Test with very high user ID that should not exist
        $nonExistentId = 999999;
        $url = self::JSONPLACEHOLDER_API . ('/users/' . $nonExistentId);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10 second timeout
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        // Should return false for non-existent resource
        $this->assertFalse($response, 'API should return error for non-existent user');

        // Check if response headers indicate 404 (if available)
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0] ?? '';
            $this->assertStringContainsString('404', $statusLine, 'HTTP status should be 404 for non-existent user');
        }
    }

    /**
     * Helper method to fetch user data from external API.
     */
    private function fetchUserDataFromApi(int $userId): array
    {
        $url = self::JSONPLACEHOLDER_API . ('/users/' . $userId);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10 second timeout
                'header' => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]
            ]
        ]);

        $json = file_get_contents($url, false, $context);

        $this->assertIsString($json, 'API should return valid JSON');

        $data = json_decode($json, true);
        $this->assertIsArray($data, 'JSON should decode to array');

        return $data;
    }
}
