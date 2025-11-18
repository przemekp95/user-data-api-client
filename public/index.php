<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\Services\UserDataService;
use App\Infrastructure\Api\GuzzleApiClient;
use App\Infrastructure\Cache\InMemoryCache;
use GuzzleHttp\Client;

// Simple dependency injection container setup
// Following Dependency Inversion Principle
$httpClient = new Client([
    'timeout' => 10,
    'connect_timeout' => 5,
]);

$apiClient = new GuzzleApiClient($httpClient);
$cache = new InMemoryCache();
$userService = new UserDataService($apiClient, $cache);

/**
 * Main endpoint handler
 * Following Single Responsibility Principle - only handles HTTP request/response.
 */
function handleRequest(UserDataService $userService): void
{
    try {
        // Get user ID from query parameter (default to 1 if not provided)
        $userId = $_GET['id'] ?? 1;

        // Validate input
        if (!is_numeric($userId) || $userId < 1) {
            sendJsonResponse(['error' => 'Invalid user ID. Must be a positive integer.'], 400);
            return;
        }

        $userId = (int) $userId;

        // Get processed user data
        $userData = $userService->getUserData($userId);

        // Send JSON response
        sendJsonResponse($userData, 200);

    } catch (Exception $exception) {
        error_log('Error processing request: ' . $exception->getMessage());

        // Don't expose internal errors to client (security best practice)
        sendJsonResponse(['error' => 'Internal server error'], 500);
    }
}

/**
 * Send JSON response following DRY principle.
 */
function sendJsonResponse(mixed $data, int $statusCode): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
}

// Security headers for API protection
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'"); // Prevent XSS, clickjacking
header('X-Frame-Options: DENY'); // Prevent clickjacking
header('X-Content-Type-Options: nosniff'); // Prevent MIME sniffing
header('Referrer-Policy: strict-origin-when-cross-origin'); // Limit referrer information
header('Permissions-Policy: geolocation=(), microphone=(), camera=()'); // Restrict permissions

// Handle CORS for development (following security practices)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
    exit;
}

// Health check endpoint for production monitoring
if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) === '/health') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');
    http_response_code(200);
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => gmdate('c'),
        'service' => 'user-data-api'
    ], JSON_THROW_ON_ERROR);
    exit;
}

// Process the request
handleRequest($userService);
