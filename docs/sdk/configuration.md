# Configuration

## Client Initialization

The SDK client accepts credentials and optional configuration:

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress(
    $apiKey,      // Required: Your API Key
    $apiSecret,   // Required: Your API Secret
    $options      // Optional: Configuration array
);
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `base_url` | string | `https://api.pigeonexpress.com/v1` | API base URL |
| `timeout` | integer | 30 | Request timeout in seconds |
| `connect_timeout` | integer | 10 | Connection timeout in seconds |
| `verify_ssl` | boolean | true | Verify SSL certificates |
| `debug` | boolean | false | Enable debug logging |

## Full Configuration Example

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress(
    'pk_live_your_api_key',
    'sk_live_your_api_secret',
    [
        'base_url' => 'https://api.pigeonexpress.com/v1',
        'timeout' => 30,
        'connect_timeout' => 10,
        'verify_ssl' => true,
        'debug' => false,
    ]
);
```

## Environment-Based Configuration

### Using Environment Variables

```php
<?php

$client = new PigeonExpress(
    getenv('PIGEON_API_KEY'),
    getenv('PIGEON_API_SECRET'),
    [
        'base_url' => getenv('PIGEON_API_URL') ?: 'https://api.pigeonexpress.com/v1',
        'timeout' => (int) getenv('PIGEON_TIMEOUT') ?: 30,
    ]
);
```

### Environment File (.env)

```env
# Pigeon Express API
PIGEON_API_KEY=pk_live_your_api_key
PIGEON_API_SECRET=sk_live_your_api_secret
PIGEON_API_URL=https://api.pigeonexpress.com/v1
PIGEON_TIMEOUT=30
```

## Sandbox vs Production

### Sandbox Environment

For testing, use the sandbox API:

```php
$client = new PigeonExpress(
    'pk_test_your_test_key',
    'sk_test_your_test_secret',
    [
        'base_url' => 'https://sandbox-api.pigeonexpress.bg/v1',
    ]
);
```

### Production Environment

```php
$client = new PigeonExpress(
    'pk_live_your_live_key',
    'sk_live_your_live_secret',
    [
        'base_url' => 'https://api.pigeonexpress.com/v1',
    ]
);
```

## Configuration Class

For advanced use, access the Configuration object:

```php
<?php

use PigeonExpress\Configuration;

// Create configuration
$config = new Configuration(
    'your_api_key',
    'your_api_secret',
    [
        'timeout' => 60,
        'debug' => true,
    ]
);

// Access configuration values
echo $config->getApiKey();
echo $config->getBaseUrl();
echo $config->getTimeout();

// Use with client
$client = new PigeonExpress($config);
```

## HTTP Client Configuration

The SDK uses Guzzle HTTP client by default. You can customize or replace it:

### Custom Guzzle Options

```php
<?php

use PigeonExpress\PigeonExpress;
use PigeonExpress\HttpClient\GuzzleHttpClient;
use GuzzleHttp\Client;

// Create custom Guzzle client
$guzzle = new Client([
    'timeout' => 60,
    'connect_timeout' => 15,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
    ],
    'proxy' => 'http://proxy.example.com:8080',
]);

// Create SDK HTTP client
$httpClient = new GuzzleHttpClient($guzzle);

// Use custom HTTP client (advanced)
$client = new PigeonExpress(
    'your_api_key',
    'your_api_secret',
    ['http_client' => $httpClient]
);
```

## Logging Configuration

Enable debug logging to troubleshoot issues:

```php
$client = new PigeonExpress(
    $apiKey,
    $apiSecret,
    ['debug' => true]
);
```

### Custom Logger (PSR-3)

```php
<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create PSR-3 logger
$logger = new Logger('pigeon');
$logger->pushHandler(new StreamHandler('path/to/pigeon.log', Logger::DEBUG));

$client = new PigeonExpress(
    $apiKey,
    $apiSecret,
    ['logger' => $logger]
);
```

## SSL Verification

For development only, you can disable SSL verification:

```php
// ⚠️ NEVER disable in production!
$client = new PigeonExpress(
    $apiKey,
    $apiSecret,
    ['verify_ssl' => false]
);
```

## Retry Configuration

Configure automatic retries for failed requests:

```php
$client = new PigeonExpress(
    $apiKey,
    $apiSecret,
    [
        'retries' => 3,           // Number of retries
        'retry_delay' => 1000,    // Delay between retries (ms)
    ]
);
```

## Best Practices

1. **Store credentials securely**
   ```php
   // Good - use environment variables
   $client = new PigeonExpress(getenv('PIGEON_KEY'), getenv('PIGEON_SECRET'));

   // Bad - hardcoded credentials
   $client = new PigeonExpress('pk_live_abc123', 'sk_live_xyz789');
   ```

2. **Use different credentials per environment**
   ```php
   $isProd = getenv('APP_ENV') === 'production';

   $client = new PigeonExpress(
       $isProd ? getenv('PIGEON_LIVE_KEY') : getenv('PIGEON_TEST_KEY'),
       $isProd ? getenv('PIGEON_LIVE_SECRET') : getenv('PIGEON_TEST_SECRET'),
       [
           'base_url' => $isProd
               ? 'https://api.pigeonexpress.com/v1'
               : 'https://sandbox-api.pigeonexpress.bg/v1'
       ]
   );
   ```

3. **Set appropriate timeouts**
   ```php
   // For quick operations (search, calculate)
   ['timeout' => 15]

   // For slower operations (create shipment with label)
   ['timeout' => 60]
   ```

4. **Enable logging in development**
   ```php
   ['debug' => getenv('APP_DEBUG') === 'true']
   ```
