# Pigeon Express PHP SDK

Official PHP SDK for integrating with the Pigeon Express courier API.

## Requirements

- PHP 7.3 or higher
- Guzzle HTTP 6.5+ or 7.x
- JSON extension

## Installation

### Via Composer

**1. Add the repository**

```bash
composer config repositories.pigeonexpress-sdk vcs https://github.com/Pigeon-Express/PHP-SDK.git
```

**2. Install the package**

```bash
composer require pigeonexpress/php-sdk
```

### Manual Installation

1. Download the SDK package
2. Extract to your project
3. Include the autoloader:

```php
require_once 'path/to/pigeonexpress-php-sdk/vendor/autoload.php';
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use PigeonExpress\PigeonExpress;

// Initialize client
$client = new PigeonExpress('your_api_key', 'your_api_secret');

// Get all offices
$offices = $client->offices()->all();

foreach ($offices as $office) {
    echo $office->getName() . ' - ' . $office->getCity()->getName() . "\n";
}
```

## Configuration

```php
$client = new PigeonExpress('your_api_key', 'your_api_secret', array(
    'base_url' => 'https://api.pigeonexpress.com',  // Optional, this is default
    'timeout' => 30,                         // Request timeout in seconds
    'debug' => false,                        // Enable debug mode
));
```

## Usage

### Offices

```php
// Get all offices
$offices = $client->offices()->all();

// Search offices
$offices = $client->offices()->search(array(
    'city' => 'София',
    'type' => 'office',
    'limit' => 10,
));

// Get offices by city ID
$offices = $client->offices()->byCity(759);

// Get only lockers
$lockers = $client->offices()->lockers();

// Search by name
$offices = $client->offices()->searchByName('Център');
```

### Cities

```php
// Get all cities
$cities = $client->cities()->all();

// Find city by ID
$city = $client->cities()->find(759);

// Search cities by name
$cities = $client->cities()->search('Варна');

// Find by postal code
$city = $client->cities()->findByPostalCode('9000');
```

### Streets

```php
// Get streets by city ID
$streets = $client->streets()->byCity(759);

// Search streets
$streets = $client->streets()->search(array(
    'city_id' => 759,
    'name' => 'Левски',
    'limit' => 20,
));

// Search streets in a specific city
$streets = $client->streets()->searchInCity(759, 'Васил Левски');
```

### Calculate Shipment Price

```php
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

// Using DTO (recommended)
$request = new CalculateShipmentRequest('office', 'address', 'standard');
$request->setPickupOfficeId(1)
        ->addPackage(new PackageData(2.5, 30, 20, 15))
        ->setCodAmount(50.00)
        ->addServiceCode('sms_notification');

$calculation = $client->shipments()->calculate($request);

echo "Delivery Fee: " . $calculation->getDeliveryFee() . " EUR\n";
echo "COD Fee: " . $calculation->getCodFee() . " EUR\n";
echo "Total: " . $calculation->getTotal() . " EUR\n";
echo "Expected delivery: " . $calculation->getExpectedDeliveryDays() . " days\n";

// Using array
$calculation = $client->shipments()->calculate(array(
    'packages' => array(
        array('weight' => 2.5, 'length' => 30, 'width' => 20, 'height' => 15)
    ),
    'pickup_type' => 'office',
    'pickup_office_id' => 1,
    'delivery_type' => 'address',
    'service_type' => 'standard',
    'cod_amount' => 50.00,
));
```

### Create Shipment

```php
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;
use PigeonExpress\DTO\Request\AddressData;

// Office-to-Office shipment
$request = new CreateShipmentRequest(
    'Иван Петров',           // receiver_name
    '+359888123456',         // receiver_phone
    'office',                // pickup_type
    'office',                // delivery_type
    'standard',              // service_type
    'sender'                 // who_pays
);

$request->setReceiverEmail('ivan@example.com')
        ->setPickupOfficeId(1)
        ->setDeliveryOfficeId(2)
        ->addPackage(
            (new PackageData(2.5, 30, 20, 15))->setDescription('Книги')
        )
        ->setCodAmount(50.00)
        ->enableSmsNotification()
        ->setNote('Моля, обадете се преди доставка');

$shipment = $client->shipments()->create($request);

echo "Reference: " . $shipment->getReferenceNumber() . "\n";
echo "Status: " . $shipment->getStatus()->getName() . "\n";
echo "Price: " . $shipment->getDeliveryPrice() . " EUR\n";

// Office-to-Address shipment
$deliveryAddress = new AddressData(759); // Varna city ID
$deliveryAddress->setStreetId(100)
                ->setStreetNumber('15')
                ->setBuildingNumber('А')
                ->setEntrance('1')
                ->setFloor('3')
                ->setApartment('12')
                ->setPostalCode('9000')
                ->setContactName('Мария Иванова')
                ->setContactPhone('+359887654321');

$request = new CreateShipmentRequest(
    'Мария Иванова',
    '+359887654321',
    'office',
    'address',
    'standard',
    'receiver'
);

$request->setPickupOfficeId(1)
        ->setDeliveryAddress($deliveryAddress)
        ->addPackage(new PackageData(1.2, 25, 15, 10))
        ->enableSmsNotification();

$shipment = $client->shipments()->create($request);
```

### Track Shipment

```php
use PigeonExpress\Exceptions\NotFoundException;

try {
    $tracking = $client->shipments()->track('PGN-2026-00012345');

    echo "Shipment: " . $tracking->getReferenceNumber() . "\n";
    echo "Status: " . $tracking->getStatus()->getName() . "\n";
    echo "Receiver: " . $tracking->getReceiverName() . "\n";

    if ($tracking->getCurrentLocation()) {
        echo "Current location: " . $tracking->getCurrentLocationName() . "\n";
    }

    echo "\nTracking History:\n";
    foreach ($tracking->getHistory() as $event) {
        echo sprintf(
            "  [%s] %s at %s\n",
            $event->getTimestamp(),
            $event->getStatusName(),
            $event->getLocation()
        );
    }

    if ($tracking->isDelivered()) {
        echo "\nShipment has been delivered!\n";
    }

} catch (NotFoundException $e) {
    echo "Shipment not found.\n";
}
```

### Read Back, Edit and Bulk Track Shipments

```php
// Everything the carrier holds, including what it charged.
$details = $client->shipments()->get('459073686609');
echo $details->getStatus()->getCode() . "\n";
$codFee = $details->getFee('cod_fee'); // FeeBreakdown or null

// Edit a shipment still in shipment_registered. Send the whole body, as on
// create: whatever is left out is cleared.
$details = $client->shipments()->update('459073686609', $request);

// Any number of references; sent in batches of 100.
foreach ($client->shipments()->trackBulk($references) as $reference => $entry) {
    echo $reference . ': ' . ($entry->isFound() ? $entry->getStatusCode() : $entry->getError()) . "\n";
}

// Status codes and their names.
$names = $client->shipmentStatuses()->namesByCode();
```

### Repeated Requests (409 and Replays)

The API deduplicates writes for 30 seconds. A request identical to one still
running is answered 409; `create()`, `update()` and the courier request calls
repeat it up to twice with the same body, waiting what `Retry-After` asks (at
most 8 seconds). Still 409 after that, a `ConflictException` is thrown and the
outcome is unknown. `setConflictRetries(0)` turns the repeats off.

A request identical to one that already succeeded is answered with that first
result, without acting again. Check it straight after the call:

```php
use PigeonExpress\Resources\AbstractResource;

$shipment = $client->shipments()->create($request);

if ($client->shipments()->getLastReplay() === AbstractResource::REPLAY_EARLIER) {
    // The shipment may have been cancelled since: read it back.
    $current = $client->shipments()->get($shipment->getReferenceNumber());
}
```

### Courier Requests

```php
$courier = $client->courierRequests()->create([
    'pickup_city_id'             => 759,
    'pickup_street_id'           => 433950,
    'pickup_street_number'       => '15',
    'requested_pickup_date'      => '2026-07-13',
    'requested_pickup_time_type' => 'interval',
    'requested_pickup_time_from' => '09:00',
    'requested_pickup_time_to'   => '13:00',
    'shipment_references'        => ['457911010807'],
]);

$courier = $client->courierRequests()->get($courier->getRequestNumber());
$client->courierRequests()->cancel($courier->getRequestNumber());
```

The API has no list of courier requests, so keep the numbers you create.

### Saved Addresses

```php
foreach ($client->addresses()->all() as $address) {
    echo $address->getId() . ' ' . $address->getLabel() . "\n";
}

$request->setSenderAddressId($client->addresses()->getDefault()->getId());
```

## Error Handling

The SDK throws specific exceptions for different error types:

```php
use PigeonExpress\Exceptions\AuthenticationException;
use PigeonExpress\Exceptions\ConflictException;
use PigeonExpress\Exceptions\ForbiddenException;
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\Exceptions\ServerException;
use PigeonExpress\Exceptions\ConnectionException;
use PigeonExpress\Exceptions\PigeonExpressException;

try {
    $shipment = $client->shipments()->create($data);

} catch (ValidationException $e) {
    // Handle validation errors (HTTP 422)
    echo "Validation error: " . $e->getMessage() . "\n";

    foreach ($e->getErrors() as $field => $messages) {
        echo "  - $field: " . implode(', ', $messages) . "\n";
    }

} catch (AuthenticationException $e) {
    // Handle authentication errors (HTTP 401)
    echo "Invalid API credentials\n";

} catch (ForbiddenException $e) {
    // Handle forbidden errors (HTTP 403)
    echo "Access denied\n";

} catch (NotFoundException $e) {
    // Handle not found errors (HTTP 404)
    echo "Resource not found\n";

} catch (ConflictException $e) {
    // An identical request is still running (HTTP 409); the outcome is unknown
    echo "Still in progress. Retry after: " . $e->getRetryAfter() . " seconds\n";

} catch (RateLimitException $e) {
    // Handle rate limit errors (HTTP 429)
    echo "Rate limit exceeded. Retry after: " . $e->getRetryAfter() . " seconds\n";

} catch (ServerException $e) {
    // Handle server errors (HTTP 500+)
    echo "Server error: " . $e->getMessage() . "\n";

} catch (ConnectionException $e) {
    // Handle connection/network errors
    echo "Connection error: " . $e->getMessage() . "\n";

} catch (PigeonExpressException $e) {
    // Handle any other API errors
    echo "API error: " . $e->getMessage() . "\n";
}
```

## Available Service Options

When creating a shipment, you can enable these additional services:

| Option | Method | Description |
|--------|--------|-------------|
| SMS Notification | `enableSmsNotification()` | SMS notification to receiver |
| Special Packaging | `enableSpecialPackaging()` | Premium packaging |
| POS Payment | `enablePosPayment()` | POS payment at delivery |
| Return at My Expense | `enableReturnAtMyExpense()` | Return shipping paid by sender |
| Requires Signature | `enableRequiresSignature()` | Signature required |
| Shipment Review | `enableShipmentReview()` | Receiver can inspect before accepting |
| Test Before Payment | `enableShipmentTestBeforePayment()` | Test product before paying |

**Note:** `shipmentReview` and `shipmentTestBeforePayment` are mutually exclusive and not available for locker delivery.

Other create fields:

| Field | Method |
|-------|--------|
| Additional services (`service_codes`) | `setServiceCodes()`, `setServiceCode()` |
| Items (`inventory_items`) | `setInventoryItems()`, `addInventoryItem()` |
| Reverse shipment | `enableReverse()`, `setSender()`, `setSenderCompany()` |
| Saved addresses | `setSenderAddressId()`, `setDeliveryAddressId()` (reverse only) |
| Return destination | `setReturnOfficeId()` or `setReturnAddressId()` |
| Return documents | `setReturnDocumentsOfficeId()` or `setReturnDocumentsAddressId()` |
| Own reference, max 100 | `setExternalReference()` |
| Label without content | `enableHideContentOnLabel()` |
| Card payment at the door | `enablePayWithPos()` |
| Anything else | `set($key, $value)` |

A locker hand-in is `pickup_type` `locker` with the locker in `setPickupOfficeId()`.
`CreateShipmentRequest::fromArray()` builds a request from a create body and
keeps every field it has no setter for, so nothing is dropped.

## Shipment Types

- **Pickup types:** `office`, `address`, `locker`
- **Delivery types:** `office`, `address`, `locker`
- **Service types:** `standard`, `express`
- **Package types:** `package`, `pallet`, `document`, `envelope`
- **Who pays:** `sender`, `receiver`

## API Reference

### Main Client

```php
$client = new PigeonExpress($apiKey, $apiSecret, $options);

$client->offices();   // Returns Offices resource
$client->cities();    // Returns Cities resource
$client->streets();   // Returns Streets resource
$client->shipments(); // Returns Shipments resource
$client->additionalServices(); // Returns AdditionalServices resource
$client->shipmentStatuses();   // Returns ShipmentStatuses resource
$client->courierRequests();    // Returns CourierRequests resource
$client->addresses();          // Returns Addresses resource
```

### Offices Resource

| Method | Description |
|--------|-------------|
| `all()` | Get all offices |
| `search($filters)` | Search offices with filters |
| `byCity($cityId, $type)` | Get offices by city |
| `byType($type)` | Get offices by type |
| `lockers($filters)` | Get lockers only |
| `searchByName($name)` | Search by name |

### Cities Resource

| Method | Description |
|--------|-------------|
| `all()` | Get all cities |
| `find($id)` | Find city by ID |
| `search($name)` | Search cities by name |
| `findByName($name)` | Find by exact name |
| `findByPostalCode($code)` | Find by postal code |

### Streets Resource

| Method | Description |
|--------|-------------|
| `byCity($cityId)` | Get streets by city |
| `search($filters)` | Search streets |
| `searchInCity($cityId, $name)` | Search in specific city |
| `find($cityId, $streetId)` | Find street by ID |

### Shipments Resource

| Method | Description |
|--------|-------------|
| `calculate($data)` | Calculate shipment price |
| `create($data)` | Create new shipment |
| `track($referenceNumber)` | Track shipment |
| `get($referenceNumber)` | Full shipment with `pricing.fees` (`ShipmentDetails`) |
| `update($referenceNumber, $data)` | Edit a newly registered shipment |
| `trackBulk($references)` | Track many shipments, 100 per request |
| `cancel($referenceNumber)` | Cancel a shipment |
| `label($referenceNumber, $format, $download)` | Fetch a label |
| `getLastReplay()` | Whether the last write was a replayed answer |

### Courier Requests Resource

| Method | Description |
|--------|-------------|
| `create($data)` | Request a courier |
| `get($requestNumber)` | Read a request back |
| `cancel($requestNumber)` | Cancel a request |

### Addresses Resource

| Method | Description |
|--------|-------------|
| `all()` | Saved addresses, the default first |
| `find($id)` | One saved address |
| `getDefault()` | The default address |

### Shipment Statuses Resource

| Method | Description |
|--------|-------------|
| `all()` | Every listed status |
| `namesByCode()` | Status names keyed by code |

## Support

For technical support, please contact:
- Email: support@pigeon.bg
- Documentation: https://docs.pigeon.bg

## License

MIT License - see LICENSE file for details.
