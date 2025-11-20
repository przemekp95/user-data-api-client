# User Data API Client

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net/)
[![Composer](https://img.shields.io/badge/Composer-2.x-orange.svg)](https://getcomposer.org/)
[![Tests](https://img.shields.io/badge/Tests-50%20✓-green.svg)](https://phpunit.readthedocs.io/)
[![License](https://img.shields.io/badge/License-MIT-lightgrey.svg)](LICENSE)

A **production-ready** PHP application that fetches user data from a public API with intelligent caching, following clean code principles and SOLID design patterns.

**✨ Key Features:**

- **Enterprise-grade architecture** with clean separation of concerns
- **Comprehensive test suite** with 50 tests covering all functionality
- **Security-first** design with multiple protection layers
- **Production-ready** deployment with Docker and automated testing
- **Performance optimized** with intelligent 60-second caching

## Features

- Fetches user data from JSONPlaceholder API
- Implements 60-second caching to reduce API calls
- Returns processed JSON response with specific fields
- Built with PHP 8.1+ following SOLID principles
- Comprehensive test coverage using TDD approach

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

You can run this application using either local PHP development environment or Docker.

### Local Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/przemekp95/user-data-api-client.git
   cd user-data-api-client
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Run tests to verify installation:

   ```bash
   composer test
   ```

   > **Expected Output:** `OK (46 tests, 226 assertions)` - confirming all functionality works correctly.

### Docker Installation

#### Option 1: Use Pre-built Container Image (Recommended)

Run the application directly from GitHub Container Registry without cloning the repository. Images are created from tested code:

```bash
# Pull the latest container image from GitHub Container Registry
docker pull ghcr.io/przemekp95/user-data-api-client:latest

# Run the container
docker run -p 8080:80 ghcr.io/przemekp95/user-data-api-client:latest

# Test the running container
curl -f "http://localhost:8080/index.php?id=1"
```

> **Expected Response:** JSON object with user data, confirming the container works correctly.

#### Option 2: Build Locally (For Development)

For customization or development, clone the repository first and build locally:

1. Clone the repository:

   ```bash
   git clone https://github.com/przemekp95/user-data-api-client.git
   cd user-data-api-client
   ```

2. Run tests locally to verify your setup before building:

   ```bash
   composer test
   ```

   > **Expected Output:** `OK (50 tests, 243 assertions)` - confirming all functionality works correctly.

3. Build and run the container locally:

   ```bash
   docker build -t user-data-api-client .
   docker run -p 8080:80 user-data-api-client
   ```

4. Test the running container:

   ```bash
   curl -f "http://localhost:8080/index.php?id=1"
   ```

## Architecture

Following clean architecture and SOLID principles.

### Domain Layer

- `UserDataDTO`: Immutable data transfer object for API responses

### Application Layer

- `ApiClientInterface`: Defines API communication contract
- `CacheInterface`: Defines caching operations contract
- `UserDataService`: Orchestrates API calls and caching (Single Responsibility)

### Infrastructure Layer

- `GuzzleApiClient`: HTTP client implementation using Guzzle
- `InMemoryCache`: Simple cache implementation with TTL support

### Presentation Layer

- `public/index.php`: HTTP endpoint with input validation and JSON responses

## Design Principles Applied

- **SOLID**:
  - Single Responsibility: Each class has one reason to change
  - Open/Closed: Extensible through interfaces
  - Liskov Substitution: Interface implementations are interchangeable
  - Interface Segregation: Focused interfaces
  - Dependency Inversion: High-level modules don't depend on low-level modules

- **DRY (Don't Repeat Yourself)**: Consistent cache key generation, error handling
- **KISS (Keep It Simple Stupid)**: Simple, focused implementations
- **YAGNI (You Aren't Gonna Need It)**: Only implemented required functionality

## Code Quality

- **Strict types**: All files use `declare(strict_types=1)`
- **PSR-4 autoloading**: Proper namespace structure
- **Input validation**: HTTP parameters validated before processing
- **Error handling**: Graceful error responses without exposing internals
- **Security**: No direct user input in API calls

## Security Features

The endpoint implements multiple layers of security protection.

### HTTP Security Headers

- **Content-Security-Policy**: Prevents XSS attacks and clickjacking (`default-src 'none'; frame-ancestors 'none'`)
- **X-Frame-Options**: DENY - Prevents clickjacking attacks
- **X-Content-Type-Options**: nosniff - Prevents MIME type sniffing
- **Referrer-Policy**: strict-origin-when-cross-origin - Limits referrer information leakage
- **Permissions-Policy**: Restricts browser permissions (geolocation, microphone, camera)

### Input Security

- **Parameter validation**: User ID must be positive integer
- **Type checking**: Strict type enforcement with PHP 8.1+
- **Input sanitization**: Numeric validation prevents injection attacks

### Access Control

- **HTTP Method restriction**: Only GET requests allowed (405 Method Not Allowed for others)
- **CORS configuration**: Controlled cross-origin access
- **JSON-only responses**: Content-Type: application/json; charset=utf-8 enforced

### Secure Error Handling

- **Fail-safe design**: Internal errors never expose sensitive information
- **Structured error responses**: Consistent JSON error format
- **Logging**: Secure error logging without user data exposure

### Rate Limiting & DDoS Protection

- **Simple rate limiting** would be recommended for production use
- **API keys/authentication** can be added when needed following Open/Closed Principle

## API Integration

- Integrates with [JSONPlaceholder](https://jsonplaceholder.typicode.com) API
- Robust error handling for network failures
- Validates API response structure
- Maps external API fields to internal domain structure

## Technical Decisions

### HTTP Client Choice

#### GuzzleHttp Client

#### HTTP Client Selection

Among the available options, the following choices were made:

- **GuzzleHttp\Client** - Implemented ✅
- `file_get_contents()` + `json_decode()` - Rejected
- Other methods (e.g., cURL, fsockopen, etc.) - Rejected

#### Justification for Choosing GuzzleHttp Client

**Advantages of GuzzleHttp\Client:**

- **Asynchronous support**: Enables future extensions with asynchronous requests without API changes
- **Exceptional error handling**: Automatic mapping of HTTP errors to PHP exceptions with context
- **Middleware Pipeline**: Easy addition of cross-cutting functionality (logging, retry, cache headers)
- **PSR-7 compliance**: Implements PSR-7 standards (HTTP Messages), ensuring interoperability
- **Rich ecosystem**: Large community, good support, regular security updates
- **Configurability**: Timeout, proxy, SSL certificates, redirect handling - all ready to use out of the box

#### Why not file_get_contents() + json_decode()?

1. **Lack of HTTP error handling**: `file_get_contents()` doesn't distinguish 4xx/5xx errors from valid responses
2. **No parallel processing**: Everything is synchronous, blocking the entire thread while waiting
3. **Limited configuration options**: No control over timeout, headers, SSL verification
4. **Security concerns**: Lack of built-in mechanisms against SSRF or injection
5. **Poor architecture**: Breaks the "Fail Fast" principle, JSON errors aren't properly handled
6. **Poor testability**: Difficult to mock or test in isolation

#### Why not cURL functions or other low-level methods?

1. **Code duplication**: Manual management of connections, headers, error codes = more boilerplate
2. **Error risk**: Lower abstraction leads to mistakes in handling edge cases
3. **Maintenance overhead**: Custom HTTP protocol implementation instead of using battle-tested library

#### Why not other high-level libraries?

Guzzle won because:

- It is the most popular and trusted HTTP library in the PHP ecosystem
- It provides optimal balance between functionality and simplicity

### Cache Implementation

#### In-Memory Cache

#### In-Memory Cache Selection

**In-Memory Cache was chosen because:**

- **Simple Requirements**: The task requires cache only for a single process/request
- **KISS Principle**: Simplest solution meeting requirements
- **Zero-dependencies**: No need for databases or external services for this task
- **Performance**: Memory is the fastest possible storage

**Rejected Alternatives:**

- **Redis/Memcached**: Overkill for single process, introduces external dependency
- **File-based cache**: Reduces performance, concurrency issues with multiple processes
- **Database cache**: Unnecessary for temporary caching, persistence overhead

Cache implements proper interface, so it can be easily replaced with any storage without changing business logic (Dependency Inversion Principle).

## Development

### Running Locally

Start a PHP development server:

```bash
cd public
php -S localhost:8000
```

Then visit: `http://localhost:8000/index.php?id=1`

### Code Style

The codebase follows PSR-12 coding standards and clean code principles:

- Descriptive variable and method names
- Single responsibility methods
- Clear documentation comments
- Consistent code formatting

### Testing

- **Comprehensive test suite** with 50 tests and 243 assertions
- **Automated testing** using Composer hooks and CI/CD pipelines
- **5-level testing pyramid**: Unit, Integration, Performance, Security, Contract
- Tests written using Test-Driven Development (TDD) approach
- Mocks used for external dependencies and third-party integrations
- Covers happy paths, error cases, edge conditions, and boundary cases
- Tests both cached and uncached scenarios with performance benchmarking

#### Test Categories

- **Unit Tests**: Individual components with isolated mocking
- **Integration Tests**: Component interaction validation
- **Performance Tests**: Speed and scalability benchmarking
- **Security Tests**: Boundary testing and injection prevention
- **Contract Tests**: External API dependency validation

## License

This project is open-source. See `LICENSE` file for details.
