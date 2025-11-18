<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use App\Application\Interfaces\ApiClientInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Guzzle-based API client implementation
 * Following Dependency Inversion Principle through interface implementation.
 */
class GuzzleApiClient implements ApiClientInterface
{
    private const BASE_URL = 'https://jsonplaceholder.typicode.com';

    public function __construct(
        private readonly ClientInterface $httpClient,
    ) {
    }

    public function fetchUserData(int $userId): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . ('/users/' . $userId));

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('API returned status code ' . $response->getStatusCode());
            }

            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new RuntimeException('API response is not a valid JSON object');
            }

            return $this->validateResponseStructure($data);
        } catch (GuzzleException $guzzleException) {
            throw new RuntimeException('Failed to fetch user data: ' . $guzzleException->getMessage(), 0, $guzzleException);
        }
    }

    /**
     * Validate that response contains required fields for our use case.
     *
     * @param  array            $data Raw API response
     * @return array            Validated response data
     * @throws RuntimeException When required fields are missing
     */
    private function validateResponseStructure(array $data): array
    {
        $requiredFields = ['id', 'name', 'email', 'address', 'company'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new RuntimeException(sprintf("Required field '%s' is missing from API response", $field));
            }
        }

        if (!is_array($data['address']) || !array_key_exists('city', $data['address'])) {
            throw new RuntimeException("Required field 'address.city' is missing from API response");
        }

        if (!is_array($data['company']) || !array_key_exists('name', $data['company'])) {
            throw new RuntimeException("Required field 'company.name' is missing from API response");
        }

        return $data;
    }
}
