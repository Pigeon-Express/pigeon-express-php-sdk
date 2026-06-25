# Pigeon Express PHP SDK

Official PHP SDK for the Pigeon Express Courier API.

## Requirements

- PHP 7.3 or higher
- Guzzle HTTP Client 6.5+ or 7.x
- JSON extension

## Installation

### Via Composer

```bash
composer require pigeonexpress/php-sdk
```

### Manual Installation

1. Download the SDK from the repository
2. Include the autoloader:

```php
require_once '/path/to/pigeonexpress-php-sdk/vendor/autoload.php';
```

## Quick Start

```php
<?php

use PigeonExpress\PigeonExpress;

// Initialize the client
$client = new PigeonExpress(
    'your_api_key',
    'your_api_secret'
);

// Search cities
$cities = $client->cities()->search('София');

// Get offices
$offices = $client->offices()->all();

// Calculate shipping
$calculation = $client->shipments()->calculate([
    'pickup_type' => 'office',
    'pickup_office_id' => 101,
    'delivery_type' => 'address',
    'service_type' => 'standard',
    'packages' => [
        ['weight' => 1.5, 'length' => 30, 'width' => 20, 'height' => 15]
    ]
]);

echo "Shipping cost: " . $calculation->getTotal() . " BGN";
```

## Documentation

- [Quick Start Guide](quick-start.md) - Get started in 5 minutes
- [Configuration](configuration.md) - All configuration options
- [Examples](examples.md) - Common use cases
- [Laravel Integration](laravel-integration.md) - Laravel-specific setup

## API Documentation

- [API Overview](../api/README.md)
- [Authentication](../api/authentication.md)
- [Cities](../api/cities.md)
- [Streets](../api/streets.md)
- [Offices](../api/offices.md)
- [Shipments](../api/shipments.md)
- [Error Handling](../api/errors.md)

## Features

### Location Services

- Search cities with municipality info
- Get streets for any city
- Find offices and lockers by city/type

### Shipping

- Calculate prices before creating shipments
- Create shipments with full tracking
- Support for COD (Cash on Delivery)
- Multiple delivery types: address, office, locker

### Developer Experience

- Fully typed DTOs for requests and responses
- Comprehensive exception handling
- PSR-4 autoloading
- PHP 7.3+ compatibility

## SDK Structure

```
pigeonexpress-php-sdk/
├── src/
│   ├── PigeonExpress.php       # Main client
│   ├── Configuration.php       # Config handling
│   ├── HttpClient/             # HTTP layer
│   │   ├── HttpClientInterface.php
│   │   ├── GuzzleHttpClient.php
│   │   └── Response.php
│   ├── Resources/              # API resources
│   │   ├── AbstractResource.php
│   │   ├── Cities.php
│   │   ├── Streets.php
│   │   ├── Offices.php
│   │   └── Shipments.php
│   ├── DTO/                    # Data Transfer Objects
│   │   ├── Request/
│   │   │   ├── PackageData.php
│   │   │   ├── AddressData.php
│   │   │   ├── CalculateShipmentRequest.php
│   │   │   └── CreateShipmentRequest.php
│   │   └── Response/
│   │       ├── City.php
│   │       ├── Street.php
│   │       ├── Office.php
│   │       ├── Shipment.php
│   │       ├── ShipmentCalculation.php
│   │       └── TrackingInfo.php
│   ├── Exceptions/             # Exception classes
│   │   ├── PigeonExpressException.php
│   │   ├── AuthenticationException.php
│   │   ├── ValidationException.php
│   │   └── ...
│   └── Contracts/
│       └── Arrayable.php
├── examples/                   # Usage examples
├── docs/                       # Documentation
└── composer.json
```

## Support

- GitHub Issues: Report bugs or request features
- Email: api-support@pigeonexpress.bg

## License

MIT License - see LICENSE file for details.
