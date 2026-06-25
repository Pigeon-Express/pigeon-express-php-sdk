# Error Handling

## Overview

The Pigeon Express API uses standard HTTP status codes and returns detailed error information in JSON format.

## Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message in Bulgarian"
  }
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Invalid credentials |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error |
| 503 | Service Unavailable - Temporary outage |

---

## Authentication Errors (401)

| Error Code | Message | Solution |
|------------|---------|----------|
| `INVALID_API_KEY` | Невалиден API ключ | Check your API Key |
| `INVALID_API_SECRET` | Невалиден API секрет | Check your API Secret |
| `CREDENTIALS_EXPIRED` | Невалидни креденциали | Contact support to renew |
| `MISSING_CREDENTIALS` | Липсващи креденциали | Include auth headers |

---

## Validation Errors (400/422)

| Error Code | Message | Solution |
|------------|---------|----------|
| `INVALID_CITY_ID` | Невалиден град | Use valid city ID from cities API |
| `INVALID_OFFICE_ID` | Невалиден офис | Use valid office ID from offices API |
| `INVALID_STREET_ID` | Невалидна улица | Use valid street ID for the city |
| `INVALID_PHONE` | Невалиден телефонен номер | Use valid Bulgarian phone format |
| `INVALID_PACKAGE_DIMENSIONS` | Невалидни размери на пакета | Check weight/dimensions limits |
| `MISSING_REQUIRED_FIELD` | Липсващо задължително поле | Check required fields |
| `INVALID_DELIVERY_TYPE` | Невалиден тип доставка | Use: address, office, or locker |
| `INVALID_SERVICE_TYPE` | Невалиден тип услуга | Use: standard or express |

---

## Business Logic Errors (400)

| Error Code | Message | Solution |
|------------|---------|----------|
| `OFFICE_CLOSED` | Офисът е затворен | Choose different office |
| `LOCKER_FULL` | Автоматът е пълен | Choose different locker |
| `CITY_NOT_SERVICED` | Градът не се обслужва | Check serviced areas |
| `PACKAGE_TOO_HEAVY` | Пратката е прекалено тежка | Max 30kg per package |
| `PACKAGE_TOO_LARGE` | Пратката е прекалено голяма | Check dimension limits |
| `COD_LIMIT_EXCEEDED` | Надвишен лимит за наложен платеж | Max COD varies by account |

---

## Server Errors (500/503)

| Error Code | Message | Description |
|------------|---------|-------------|
| `INTERNAL_ERROR` | Вътрешна грешка | Retry or contact support |
| `SERVICE_UNAVAILABLE` | Външната система е временно недостъпна | Temporary outage, retry later |
| `TIMEOUT` | Времето за изчакване изтече | Retry the request |

---

## SDK Exception Handling

The SDK throws specific exceptions for different error types:

```php
<?php

use PigeonExpress\PigeonExpress;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\AuthenticationException;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\Exceptions\ServerException;

$client = new PigeonExpress($apiKey, $apiSecret);

try {
    $shipment = $client->shipments()->create($request);
} catch (AuthenticationException $e) {
    // Invalid API credentials
    echo "Auth error: " . $e->getMessage();
} catch (ValidationException $e) {
    // Invalid request data
    echo "Validation error: " . $e->getMessage();
    // Get validation details
    $errors = $e->getErrors();
} catch (NotFoundException $e) {
    // Resource not found (city, office, etc.)
    echo "Not found: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Too many requests
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
} catch (ServerException $e) {
    // Server error - retry later
    echo "Server error: " . $e->getMessage();
} catch (PigeonExpressException $e) {
    // Generic API error
    echo "API error: " . $e->getMessage();
    echo "Error code: " . $e->getErrorCode();
}
```

---

## Exception Classes

| Exception | HTTP Code | Description |
|-----------|-----------|-------------|
| `AuthenticationException` | 401 | Invalid credentials |
| `ValidationException` | 400, 422 | Invalid request data |
| `NotFoundException` | 404 | Resource not found |
| `RateLimitException` | 429 | Rate limit exceeded |
| `ServerException` | 500, 503 | Server-side error |
| `NetworkException` | - | Connection error |
| `PigeonExpressException` | Any | Base exception class |

---

## Retry Strategy

For temporary errors (503, timeouts), implement exponential backoff:

```php
<?php

function createShipmentWithRetry($client, $request, $maxRetries = 3)
{
    $attempt = 0;
    $delay = 1; // Start with 1 second

    while ($attempt < $maxRetries) {
        try {
            return $client->shipments()->create($request);
        } catch (ServerException $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw $e;
            }
            sleep($delay);
            $delay *= 2; // Exponential backoff
        }
    }
}
```

---

## Common Issues

### "Външната система е временно недостъпна"

This means the Pigeon Express API is temporarily unavailable. Actions:

1. Retry after a few minutes
2. Check Pigeon Express status page
3. Contact support if issue persists

### "Невалиден град" / "Невалидна улица"

The city or street ID doesn't exist or is invalid:

1. Refresh your local city/street cache
2. Re-sync locations using the sync command
3. Verify the ID exists in the API

### "Невалиден телефонен номер"

Phone number format is incorrect:

- Use format: `+359XXXXXXXXX` or `0XXXXXXXXX`
- 9 digits after country code
- Valid Bulgarian mobile prefixes: 87, 88, 89, 98, 99

---

## Logging Errors

The SDK logs all errors automatically when used with Laravel:

```php
// In Laravel, errors are logged to storage/logs/laravel.log
// Log level: error

// Manual logging
use Illuminate\Support\Facades\Log;

try {
    $client->shipments()->create($request);
} catch (PigeonExpressException $e) {
    Log::error('Pigeon Express error', [
        'message' => $e->getMessage(),
        'code' => $e->getErrorCode(),
        'request' => $request->toArray(),
    ]);
}
```
