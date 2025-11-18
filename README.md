# User Data API Client

A simple PHP application that fetches user data from a public API with caching functionality, following clean code principles and SOLID design patterns.

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

1. Clone the repository:
```bash
git clone <repository-url>
cd user-data-api-client
```

2. Install dependencies:
```bash
composer install
```

## Usage

### API Endpoint

The application provides a single HTTP endpoint:

**GET** `/public/index.php?id={user_id}`

Parameters:
- `id` (optional): User ID to fetch (defaults to 1)

### Response Format

Returns JSON with the following structure:
```json
{
  "id": 1,
  "name": "Leanne Graham",
  "email": "Sincere@april.biz",
  "city": "Gwenborough",
  "company": "Romaguera-Crona"
}
```

### Error Responses

- **400 Bad Request**: Invalid user ID (non-positive integer)
- **500 Internal Server Error**: API failures or internal errors

### Caching Behavior

- Data is cached for 60 seconds
- Subsequent requests for the same user within the cache period return cached data
- Reduces external API calls following the DRY principle

## Running Tests

Execute all tests using PHPUnit:

```bash
composer test
# or
php vendor/bin/phpunit
```

### Test Coverage

- **UserDataDTOTest**: Tests data transfer object functionality
- **UserDataServiceTest**: Tests business logic and caching behavior
- **InMemoryCacheTest**: Tests cache operations and expiration

## Architecture

Following clean architecture and SOLID principles:

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

The endpoint implements multiple layers of security protection:

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

### HTTP Client Choice: GuzzleHttp\Client

#### Wybór GuzzleHttp\Client
Spośród dostępnych opcji zostały wybrane następujące opcje:
- **GuzzleHttp\Client** - Zaimplementowana ✅
- `file_get_contents()` + `json_decode()` - Odrzucona
- Inne metody (np. cURL, fsockopen, itd.) - Odrzucone

#### Uzasadnienie wyboru GuzzleHttp\Client:

**Zalety GuzzleHttp\Client:**
- **Asynchroniczność**: Obsługuje zapytania asynchroniczne, co pozwoli na przyszłe rozszerzenia bez zmiany API
- **Wyjątkowa obsługa błędów**: Automatyczne mapowanie błędów HTTP na wyjątki PHP z kontekstem
- **Middleware Pipeline**: Łatwe dodanie funkcjonalności cross-cutting (logowanie, retry, cache headers)
- **PSR-7 zgodność**: Implementuje standardy PSR-7 (HTTP Messages), zapewniając interoperacyjność
- **Bogaty ekosystem**: Duża społeczność, dobre wsparcie, regularne aktualizacje bezpieczeństwa
- **Konfigurowalność**: Timeout, proxy, certyfikaty SSL, redirect handling - wszystko gotowe do użycia

#### Dlaczego nie file_get_contents() + json_decode()?

1. **Brak obsługi błędów HTTP**: `file_get_contents()` nie rozróżnia błędów 4xx/5xx od prawidłowych odpowiedzi
2. **Brak równoległego przetwarzania**: Wszystko synchroniczne, zablokuje cały wątek podczas oczekiwania
3. **Ograniczone opcje konfiguracyjne**: Brak kontroli timeout, headers, SSL verification
4. **Security concerns**: Brak wbudowanych mechanizmów przeciwko SSRF czy injection
5. **Nieprzemyślana architektura**: Łamie zasade "Fail Fast", błędy JSON nie są odpowiednio obsłużone
6. **Słaba testowalność**: Trudno mockować czy testować w izolacji

#### Dlaczego nie cURL functions ani inne niskopoziomowe metody?

1. **Duplikacja kodu**: Manuelne zarządzanie connections, headers, error codes = więcej boilerplate
2. **Ryzyko błędów**: Niższa abstrakcja prowadzi do pomyłek w obsłudze edge cases
3. **Maintenance overhead**: Własna implementacja protokołu HTTP zamiast używania battle-tested library

#### Dlaczego nie inne high-level biblioteki?

Guzzle wygrał ponieważ:
- Jest najbardziej popularną i zaufaną biblioteką HTTP w PHP ekosystemie
- Ma najlepsze wsparcie PSR-7, co czyni go przyszłościowo kompatybilnym
- Zapewnia optymalną równowagę między funkcjonalnością a prostotą

### Cache Implementation: InMemoryCache

#### Wybór In-Memory Cache zamiast innych rozwiązań:

**In-Memory Cache wybrany ponieważ:**
- **Simple Requirements**: Zadanie wymaga cache tylko dla pojedynczego procesu/requestu
- **KISS Principle**: Najprostsze rozwiązanie spełniające wymagania
- **Zero-dependencies**: Brak potrzeby baz danych czy zewnętrznych usług dla takiego zadania
- **Performance**: Pamięć оперативna jest najszybszym możliwym storage

**Alternatywy odrzucone:**
- **Redis/Memcached**: Overkill dla pojedynczego procesu, wprowadza external dependency
- **File-based cache**: Obniża performance, concurrency issues przy wielu procesach
- **Database cache**: Zbyteczna dla tymczasového cachewania, overhead persistencji

Cache implementuje właściwy interface, więc można latwo zamienić na dowolny storage bez zmiany business logic (Dependency Inversion Principle).

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

- Tests written before implementation (TDD)
- Mocks used for external dependencies
- Covers happy paths, error cases, and edge conditions
- Tests both cached and uncached scenarios

## License

This project is open-source. See `LICENSE` file for details.
