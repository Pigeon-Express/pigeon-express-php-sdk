# Code Examples

Common use cases and code examples for the Pigeon Express PHP SDK.

## Table of Contents

- [Basic Operations](#basic-operations)
- [Address Delivery](#address-delivery)
- [Office Delivery](#office-delivery)
- [Locker Delivery](#locker-delivery)
- [Cash on Delivery (COD)](#cash-on-delivery-cod)
- [Error Handling](#error-handling)
- [Batch Operations](#batch-operations)

---

## Basic Operations

### Initialize Client

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress(
    getenv('PIGEON_API_KEY'),
    getenv('PIGEON_API_SECRET')
);
```

### Search Cities

```php
// Search by name
$cities = $client->cities()->search('Варна');

foreach ($cities as $city) {
    printf(
        "%s. %s, общ. %s (%s)\n",
        $city->getType() === 'град' ? 'гр' : 'с',
        $city->getName(),
        $city->getMunicipality(),
        $city->getPostalCode()
    );
}
```

### Get All Offices in a City

```php
$offices = $client->offices()->search([
    'city_id' => 2, // Plovdiv
    'type' => 'office'
]);

foreach ($offices as $office) {
    echo $office->getName() . "\n";
    echo "  Address: " . $office->getAddress() . "\n";

    if ($wh = $office->getWorkingHours()) {
        echo "  Monday: " . ($wh->getMonday() ?? 'Closed') . "\n";
    }
}
```

### Find Lockers

```php
$lockers = $client->offices()->search([
    'type' => 'locker',
    'query' => 'Мол'
]);

foreach ($lockers as $locker) {
    echo $locker->getName() . " - " . $locker->getAddress() . "\n";
    if ($locker->getWorkingHours()?->is24Hours()) {
        echo "  ✓ Open 24/7\n";
    }
}
```

---

## Address Delivery

### Full Address Delivery Example

```php
<?php

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\AddressData;
use PigeonExpress\DTO\Request\PackageData;

$client = new PigeonExpress($apiKey, $apiSecret);

// 1. Find city
$cities = $client->cities()->search('София');
$sofia = $cities[0];

// 2. Find street
$streets = $client->streets()->search($sofia->getId(), 'Витоша');
$street = $streets[0];

// 3. Create shipment
$request = new CreateShipmentRequest(
    'Петър Георгиев',
    '+359888111222',
    'office',       // pickup from office
    'address',      // deliver to address
    'standard',
    'receiver'
);

$request->setReceiverEmail('petar@example.com');
$request->setPickupOfficeId(101);

// 4. Set address
$address = new AddressData($sofia->getId());
$address->setStreetId($street->getId());
$address->setStreetNumber('100');
$address->setBuildingNumber('Б');
$address->setEntrance('2');
$address->setFloor('5');
$address->setApartment('15');

$request->setDeliveryAddress($address);

// 5. Add package
$request->addPackage(new PackageData(2.0, 35, 25, 15));
$request->setCodAmount(120.00);
$request->setNote('Звънец не работи, моля обадете се.');

// 6. Create
$shipment = $client->shipments()->create($request);
echo "Created: " . $shipment->getReferenceNumber() . "\n";
```

### Address Without Street ID

For cities without street-level data:

```php
$address = new AddressData($cityId);
$address->setNote('ул. Централна 5, до магазин Лидл');
$request->setDeliveryAddress($address);
```

---

## Office Delivery

### Delivery to Pigeon Express Office

```php
<?php

use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

// Find an office
$offices = $client->offices()->search([
    'city_id' => 2,
    'type' => 'office'
]);
$deliveryOffice = $offices[0];

// Create shipment
$request = new CreateShipmentRequest(
    'Анна Димитрова',
    '+359877333444',
    'office',      // pickup
    'office',      // deliver to office
    'standard',
    'receiver'
);

$request->setPickupOfficeId(101);
$request->setDeliveryOfficeId($deliveryOffice->getId());
$request->addPackage(new PackageData(0.5, 20, 15, 10));
$request->setCodAmount(35.00);

$shipment = $client->shipments()->create($request);
echo "Deliver to: " . $deliveryOffice->getName() . "\n";
echo "Reference: " . $shipment->getReferenceNumber() . "\n";
```

---

## Locker Delivery

### Delivery to Automated Locker

```php
<?php

use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

// Find a locker
$lockers = $client->offices()->search([
    'city_id' => 1,
    'type' => 'locker'
]);
$locker = $lockers[0];

// Create shipment
$request = new CreateShipmentRequest(
    'Стефан Колев',
    '+359899555666',  // Phone is CRITICAL for locker code
    'office',
    'locker',
    'standard',
    'receiver'
);

$request->setPickupOfficeId(101);
$request->setDeliveryOfficeId($locker->getId());
$request->addPackage(new PackageData(1.0, 25, 20, 15));
$request->setCodAmount(75.00);

// SMS is required for locker delivery!
$request->enableSmsNotification();

$shipment = $client->shipments()->create($request);

echo "Locker: " . $locker->getName() . "\n";
echo "Reference: " . $shipment->getReferenceNumber() . "\n";
echo "Customer will receive SMS with locker code.\n";
```

---

## Cash on Delivery (COD)

### Calculate with COD

```php
<?php

use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

$request = new CalculateShipmentRequest('office', 'office', 'standard');
$request->setPickupOfficeId(101);
$request->setDeliveryOfficeId(201);
$request->addPackage(new PackageData(1.0, 20, 15, 10));

// With COD
$request->setCodAmount(100.00);
$withCod = $client->shipments()->calculate($request);

echo "With COD:\n";
echo "  Shipping: " . $withCod->getDeliveryFee() . " BGN\n";
echo "  COD Fee: " . $withCod->getCodFee() . " BGN\n";
echo "  Total: " . $withCod->getTotal() . " BGN\n";

// Without COD
$request->setCodAmount(0);
$withoutCod = $client->shipments()->calculate($request);

echo "\nWithout COD:\n";
echo "  Shipping: " . $withoutCod->getDeliveryFee() . " BGN\n";
echo "  Total: " . $withoutCod->getTotal() . " BGN\n";
```

### COD Best Practices

```php
// COD amount = product value only (without delivery fee)
$productValue = 89.90;
$request->setCodAmount($productValue);

// Delivery fee is handled separately based on who_pays
$request->setWhoPays('receiver'); // Customer pays delivery + COD amount
```

---

## Error Handling

### Comprehensive Error Handling

```php
<?php

use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\AuthenticationException;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\Exceptions\ServerException;

try {
    $shipment = $client->shipments()->create($request);
    echo "Success! Reference: " . $shipment->getReferenceNumber();

} catch (AuthenticationException $e) {
    // Invalid API credentials
    error_log("Auth failed: " . $e->getMessage());
    echo "Configuration error. Please contact support.";

} catch (ValidationException $e) {
    // Invalid request data
    echo "Validation error: " . $e->getMessage();

    // Show specific field errors
    foreach ($e->getErrors() as $field => $error) {
        echo "  - {$field}: {$error}\n";
    }

} catch (NotFoundException $e) {
    // City, office, or street not found
    echo "Location not found: " . $e->getMessage();

} catch (ServerException $e) {
    // API is temporarily unavailable
    error_log("Server error: " . $e->getMessage());
    echo "Service temporarily unavailable. Please try again later.";

} catch (PigeonExpressException $e) {
    // Any other API error
    error_log("API error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
```

---

## Batch Operations

### Calculate Multiple Shipments

```php
<?php

$destinations = [
    ['city_id' => 1, 'name' => 'София'],
    ['city_id' => 2, 'name' => 'Пловдив'],
    ['city_id' => 3, 'name' => 'Варна'],
];

$package = new PackageData(1.0, 20, 15, 10);

foreach ($destinations as $dest) {
    $request = new CalculateShipmentRequest('office', 'address', 'standard');
    $request->setPickupOfficeId(101);
    $request->setDeliveryCityId($dest['city_id']);
    $request->addPackage($package);

    try {
        $calc = $client->shipments()->calculate($request);
        printf(
            "%s: %.2f BGN (%d days)\n",
            $dest['name'],
            $calc->getTotal(),
            $calc->getExpectedDeliveryDays()
        );
    } catch (PigeonExpressException $e) {
        echo "{$dest['name']}: Error - {$e->getMessage()}\n";
    }
}
```

### Track Multiple Shipments

```php
<?php

$references = [
    'PGN-2026-00012345',
    'PGN-2026-00012346',
    'PGN-2026-00012347',
];

foreach ($references as $ref) {
    try {
        $tracking = $client->shipments()->track($ref);

        printf(
            "%s: %s - %s\n",
            $ref,
            $tracking->getStatus(),
            $tracking->getStatusDescription()
        );

    } catch (NotFoundException $e) {
        echo "{$ref}: Not found\n";
    }
}
```

---

## Helper Functions

### Format City Name

```php
function formatCityName($city): string
{
    $prefix = match ($city->getType()) {
        'село' => 'с.',
        'град' => 'гр.',
        'к.к.', 'курорт' => 'к.к.',
        default => 'гр.',
    };

    $name = $prefix . ' ' . $city->getName();

    if ($city->getMunicipality()) {
        $name .= ', общ. ' . $city->getMunicipality();
    }

    return $name;
}

// Usage
$city = $client->cities()->find(12345);
echo formatCityName($city); // "с. Абланица, общ. Велинград"
```

### Format Street Name

```php
function formatStreetName($street): string
{
    $prefix = match ($street->getType()) {
        'булевард' => 'бул.',
        'площад' => 'пл.',
        'алея' => 'ал.',
        'махала' => 'мах.',
        'местност' => 'м.',
        'квартал' => 'кв.',
        default => 'ул.',
    };

    return $prefix . ' ' . $street->getName();
}
```

### Format Working Hours

```php
function formatWorkingHours($office): string
{
    $wh = $office->getWorkingHours();

    if (!$wh) {
        return 'Няма информация';
    }

    if ($wh->is24Hours()) {
        return '24/7';
    }

    $days = [
        'monday' => 'Пон',
        'tuesday' => 'Вто',
        'wednesday' => 'Сря',
        'thursday' => 'Чет',
        'friday' => 'Пет',
        'saturday' => 'Съб',
        'sunday' => 'Нед',
    ];

    $lines = [];
    foreach ($days as $method => $label) {
        $getter = 'get' . ucfirst($method);
        $hours = $wh->$getter();
        $lines[] = $label . ': ' . ($hours ?? 'Затворено');
    }

    return implode(', ', $lines);
}
```
