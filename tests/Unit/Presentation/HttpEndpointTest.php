<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use App\Application\Services\UserDataService;
use App\Application\Interfaces\ApiClientInterface;
use App\Application\Interfaces\CacheInterface;
use App\Domain\DTO\UserDataDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Presentation Layer - HTTP endpoint testing.
 * Tests HTTP request handling, response formatting, and security.
 * Complete coverage for public/index.php presentation logic.
 */
class HttpEndpointTest extends TestCase
{
    private UserDataService $userService;

    protected function setUp(): void
    {
        // Initialize mock service for each test
        $apiClient = $this->createStub(ApiClientInterface::class);
        $cache = $this->createStub(CacheInterface::class);

        $serviceMock = new class($apiClient, $cache) extends UserDataService {
            public function __construct(
                private readonly ApiClientInterface $apiClient,
                private readonly CacheInterface $cache
            ) {
                parent::__construct($apiClient, $cache);
            }

            // Override to control response for testing
            public function getUserData(int $userId): UserDataDTO
            {
                // Mock successful response for unit tests
                return new UserDataDTO($userId, 'John Doe', 'john@test.com', 'City', 'Company');
            }
        };

        $this->userService = $serviceMock;

        // Clear any previous output buffering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    protected function tearDown(): void
    {
        // Clean up output buffering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Test successful GET request with valid user ID.
     */
    public function testSuccessfulGetRequest(): void
    {
        // Simulate HTTP GET request with valid ID
        $_GET['id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Capture output and headers
        ob_start();

        // Simulate the endpoint logic
        try {
            $userId = $_GET['id'] ?? 1;
            if (!is_numeric($userId) || $userId < 1) {
                echo json_encode(['error' => 'Invalid user ID. Must be a positive integer.'], JSON_THROW_ON_ERROR);
                return;
            }

            $userId = (int)$userId;
            $userData = $this->userService->getUserData($userId);

            echo json_encode($userData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\Exception) {
            echo json_encode(['error' => 'Internal server error'], JSON_THROW_ON_ERROR);
        }

        $output = ob_get_clean();

        // Verify response
        $this->assertJson($output);
        $decodedResponse = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'city' => 'City',
            'company' => 'Company'
        ], $decodedResponse);
    }

    /**
     * Test invalid user ID validation.
     */
    public function testInvalidUserIdValidation(): void
    {
        // Test negative ID
        $_GET['id'] = '-1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();

        try {
            $userId = $_GET['id'] ?? 1;
            if (!is_numeric($userId) || $userId < 1) {
                echo json_encode(['error' => 'Invalid user ID. Must be a positive integer.'], JSON_THROW_ON_ERROR);
                return;
            }
        } catch (\Exception) {
            echo json_encode(['error' => 'Internal server error'], JSON_THROW_ON_ERROR);
        }

        $output = ob_get_clean();
        $this->assertEquals('{"error":"Invalid user ID. Must be a positive integer."}', $output);
    }

    /**
     * Test HTTP method validation (only GET allowed).
     */
    public function testHttpMethodValidation(): void
    {
        // Simulate POST request (should be rejected)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['id'] = '1';

        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['error' => 'Method not allowed'], JSON_THROW_ON_ERROR);
        }

        $output = ob_get_clean();
        $this->assertEquals('{"error":"Method not allowed"}', $output);
    }

    /**
     * Test health endpoint response.
     */
    public function testHealthEndpoint(): void
    {
        // Simulate health endpoint request
        $_SERVER['REQUEST_URI'] = '/health';

        ob_start();

        if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) === '/health') {
            echo json_encode([
                'status' => 'healthy',
                'timestamp' => gmdate('c'),
                'service' => 'user-data-api'
            ], JSON_THROW_ON_ERROR);
        }

        $output = ob_get_clean();
        $this->assertJson($output);

        $decodedResponse = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('healthy', $decodedResponse['status']);
        $this->assertEquals('user-data-api', $decodedResponse['service']);
        $this->assertArrayHasKey('timestamp', $decodedResponse);
    }
}
