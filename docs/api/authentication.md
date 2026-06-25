# Authentication

## Overview

Pigeon Express API uses API Key and Secret for authentication. All requests must include authentication headers.

## Credentials

You will receive your credentials after registration:

- **API Key**: Your unique identifier (e.g., `pk_live_abc123...`)
- **API Secret**: Your secret key (e.g., `sk_live_xyz789...`)

> **Security Note**: Never expose your API Secret in client-side code or public repositories.

## Authentication Headers

Include these headers in every request:

```http
X-API-Key: your_api_key
X-API-Secret: your_api_secret
Content-Type: application/json
Accept: application/json
```

## Example Request

```bash
curl -X GET "https://api.pigeonexpress.com/v1/cities" \
  -H "X-API-Key: pk_live_abc123" \
  -H "X-API-Secret: sk_live_xyz789" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

## PHP Example

```php
<?php

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.pigeonexpress.com/v1/cities',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-API-Key: ' . $apiKey,
        'X-API-Secret: ' . $apiSecret,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
```

## Using the SDK

The SDK handles authentication automatically:

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress(
    'pk_live_abc123',  // API Key
    'sk_live_xyz789'   // API Secret
);

// Authentication is handled automatically
$cities = $client->cities()->all();
```

## Environment Types

| Environment | Key Prefix | URL |
|-------------|------------|-----|
| Production | `pk_live_`, `sk_live_` | api.pigeonexpress.bg |
| Sandbox | `pk_test_`, `sk_test_` | sandbox-api.pigeonexpress.bg |

## Authentication Errors

| HTTP Code | Error Code | Description |
|-----------|------------|-------------|
| 401 | `INVALID_API_KEY` | API Key is invalid or missing |
| 401 | `INVALID_API_SECRET` | API Secret is invalid or missing |
| 401 | `CREDENTIALS_EXPIRED` | Credentials have expired |
| 403 | `ACCOUNT_SUSPENDED` | Account has been suspended |
| 429 | `RATE_LIMIT_EXCEEDED` | Too many requests |

## Best Practices

1. **Store credentials securely**: Use environment variables or secure vaults
2. **Use different keys per environment**: Separate sandbox and production credentials
3. **Rotate secrets regularly**: Update your API Secret periodically
4. **Monitor usage**: Track API calls for unusual activity

## SDK Configuration

```php
<?php

// Using environment variables (recommended)
$client = new PigeonExpress(
    getenv('PIGEON_API_KEY'),
    getenv('PIGEON_API_SECRET'),
    [
        'base_url' => getenv('PIGEON_API_URL'), // Optional
        'timeout' => 30,
    ]
);
```
