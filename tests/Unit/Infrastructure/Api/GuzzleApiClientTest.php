<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Api;

use App\Infrastructure\Api\GuzzleApiClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Test suite for Guzzle API client implementation
 * Following Test-Driven Development principles.
 */
class GuzzleApiClientTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&ClientInterface */
    private ClientInterface $httpClient;

    private GuzzleApiClient $apiClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->apiClient = new GuzzleApiClient($this->httpClient);
    }

    public function testFetchUserDataReturnsValidResponse(): void
    {
        $userId = 1;
        $expectedApiResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
            'address' => [
                'street' => 'Kulas Light',
                'city' => 'Gwenborough'
            ],
            'company' => [
                'name' => 'Romaguera-Crona'
            ]
        ];

        $response = new Response(200, [], json_encode($expectedApiResponse, JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $result = $this->apiClient->fetchUserData($userId);

        self::assertEquals($expectedApiResponse, $result);
    }

    public function testFetchUserDataThrowsExceptionForNon200Status(): void
    {
        $userId = 1;
        $response = new Response(404, [], 'Not Found');

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API returned status code 404');

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForNonArrayResponse(): void
    {
        $userId = 1;
        $response = new Response(200, [], json_encode('invalid', JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API response is not a valid JSON object');

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForMissingRequiredField(): void
    {
        $userId = 1;
        $invalidResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'address' => ['city' => 'Gwenborough'],
            'company' => ['name' => 'Romaguera-Crona']
            // Missing 'email' field
        ];

        $response = new Response(200, [], json_encode($invalidResponse, JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Required field 'email' is missing from API response");

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForInvalidAddressStructure(): void
    {
        $userId = 1;
        $invalidResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'test@example.com',
            'address' => 'invalid_address', // Should be array
            'company' => ['name' => 'Romaguera-Crona']
        ];

        $response = new Response(200, [], json_encode($invalidResponse, JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Required field 'address.city' is missing from API response");

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForMissingCityInAddress(): void
    {
        $userId = 1;
        $invalidResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'test@example.com',
            'address' => ['street' => 'Test St'], // Missing 'city'
            'company' => ['name' => 'Romaguera-Crona']
        ];

        $response = new Response(200, [], json_encode($invalidResponse, JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Required field 'address.city' is missing from API response");

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForInvalidCompanyStructure(): void
    {
        $userId = 1;
        $invalidResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'test@example.com',
            'address' => ['city' => 'Gwenborough'],
            'company' => 'invalid_company' // Should be array
        ];

        $response = new Response(200, [], json_encode($invalidResponse, JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Required field 'company.name' is missing from API response");

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForMissingCompanyName(): void
    {
        $userId = 1;
        $invalidResponse = [
            'id' => 1,
            'name' => 'Leanne Graham',
            'email' => 'test@example.com',
            'address' => ['city' => 'Gwenborough'],
            'company' => ['catchPhrase' => 'Test phrase'] // Missing 'name'
        ];

        $response = new Response(200, [], json_encode($invalidResponse, JSON_THROW_ON_ERROR));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Required field 'company.name' is missing from API response");

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForGuzzleHttpError(): void
    {
        $userId = 1;
        $request = new Request('GET', 'https://jsonplaceholder.typicode.com/users/1');
        $exception = new RequestException('Connection failed', $request);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user data: Connection failed');

        $this->apiClient->fetchUserData($userId);
    }

    public function testFetchUserDataThrowsExceptionForInvalidJsonResponse(): void
    {
        $userId = 1;
        $response = new Response(200, [], 'invalid json {');

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://jsonplaceholder.typicode.com/users/1')
            ->willReturn($response);

        $this->expectException(\JsonException::class);

        $this->apiClient->fetchUserData($userId);
    }
}
