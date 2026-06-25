# Quick Start Guide

Get up and running with the Pigeon Express PHP SDK in 5 minutes.

## Step 1: Install the SDK

```bash
composer require pigeonexpress/php-sdk
```

## Step 2: Initialize the Client

```php
<?php

require_once 'vendor/autoload.php';

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress(
    'your_api_key',
    'your_api_secret'
);
```

## Step 3: Search for a City

```php
// Search cities by name
$cities = $client->cities()->search('Пловдив');

foreach ($cities as $city) {
    echo $city->getId() . ': ' . $city->getName() . "\n";
    echo '  Община: ' . $city->getMunicipality() . "\n";
}
```

## Step 4: Find an Office

```php
// Get offices in the city
$offices = $client->offices()->search([
    'city_id' => $cities[0]->getId(),
    'type' => 'office'
]);

foreach ($offices as $office) {
    echo $office->getName() . ' - ' . $office->getAddress() . "\n";
}
```

## Step 5: Calculate Shipping Price

```php
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

// Create calculation request
$request = new CalculateShipmentRequest(
    'office',    // pickup_type
    'office',    // delivery_type
    'standard'   // service_type
);

$request->setPickupOfficeId(101);  // Your pickup office
$request->setDeliveryOfficeId($offices[0]->getId());

// Add package (weight: 1kg, dimensions: 20x15x10 cm)
$request->addPackage(new PackageData(1.0, 20, 15, 10));

// Optional: add COD
$request->setCodAmount(50.00);

// Calculate
$calculation = $client->shipments()->calculate($request);

echo "Delivery fee: " . $calculation->getDeliveryFee() . " BGN\n";
echo "Total: " . $calculation->getTotal() . " BGN\n";
echo "Estimated delivery: " . $calculation->getExpectedDeliveryDays() . " days\n";
```

## Step 6: Create a Shipment

```php
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

// Create shipment request
$request = new CreateShipmentRequest(
    'Иван Петров',       // receiver_name
    '+359888123456',     // receiver_phone
    'office',            // pickup_type
    'office',            // delivery_type
    'standard',          // service_type
    'receiver'           // who_pays
);

$request->setPickupOfficeId(101);
$request->setDeliveryOfficeId($offices[0]->getId());
$request->addPackage(new PackageData(1.0, 20, 15, 10));
$request->setCodAmount(50.00);
$request->enableSmsNotification();

// Create the shipment
try {
    $shipment = $client->shipments()->create($request);

    echo "Shipment created!\n";
    echo "Reference: " . $shipment->getReferenceNumber() . "\n";
    echo "Tracking: " . $shipment->getFirstPackage()->getTrackingNumber() . "\n";
    echo "Label URL: " . $shipment->getLabelUrl() . "\n";

} catch (\PigeonExpress\Exceptions\PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Step 7: Track a Shipment

```php
$tracking = $client->shipments()->track('PGN-2026-00012345');

echo "Status: " . $tracking->getStatus() . "\n";
echo "Description: " . $tracking->getStatusDescription() . "\n";

echo "\nTracking History:\n";
foreach ($tracking->getEvents() as $event) {
    echo sprintf(
        "[%s] %s\n",
        $event->getTimestamp()->format('d.m.Y H:i'),
        $event->getDescription()
    );
}
```

## Complete Example

Here's a complete working example:

```php
<?php

require_once 'vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;
use PigeonExpress\DTO\Request\AddressData;
use PigeonExpress\Exceptions\PigeonExpressException;

// Initialize
$client = new PigeonExpress(
    getenv('PIGEON_API_KEY'),
    getenv('PIGEON_API_SECRET')
);

try {
    // 1. Find the destination city
    $cities = $client->cities()->search('Пловдив');
    $city = $cities[0];
    echo "Delivering to: {$city->getName()}, {$city->getMunicipality()}\n";

    // 2. Get streets for address delivery
    $streets = $client->streets()->search($city->getId(), 'Васил Левски');
    $street = $streets[0];
    echo "Street: {$street->getName()}\n";

    // 3. Create shipment request
    $request = new CreateShipmentRequest(
        'Мария Иванова',
        '+359887654321',
        'office',
        'address',
        'standard',
        'receiver'
    );

    $request->setReceiverEmail('maria@example.com');
    $request->setPickupOfficeId(101); // Your default pickup office

    // 4. Set delivery address
    $address = new AddressData($city->getId());
    $address->setStreetId($street->getId());
    $address->setStreetNumber('15');
    $address->setFloor('2');
    $address->setApartment('5');
    $request->setDeliveryAddress($address);

    // 5. Add package
    $package = new PackageData(2.5, 40, 30, 20);
    $package->setDescription('Дрехи');
    $request->addPackage($package);

    // 6. Set COD (product value)
    $request->setCodAmount(89.90);
    $request->enableSmsNotification();
    $request->setNote('Моля, обадете се 30 мин. преди доставка');

    // 7. Create shipment
    $shipment = $client->shipments()->create($request);

    echo "\n✅ Shipment Created Successfully!\n";
    echo "Reference: {$shipment->getReferenceNumber()}\n";
    echo "Tracking: {$shipment->getFirstPackage()->getTrackingNumber()}\n";
    echo "Label: {$shipment->getLabelUrl()}\n";

} catch (PigeonExpressException $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}
```

## Next Steps

- [Configuration Options](configuration.md) - Customize SDK behavior
- [Examples](examples.md) - More use cases
- [Laravel Integration](laravel-integration.md) - Laravel-specific setup
- [API Documentation](../api/README.md) - Full API reference
