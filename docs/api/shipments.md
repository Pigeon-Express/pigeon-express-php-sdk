# Shipments API

## Overview

The Shipments API is the core of Pigeon Express integration. It allows you to:

- **Calculate** shipping prices before creating shipments
- **Create** shipments with tracking
- **Track** shipment status and delivery progress

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/shipments/calculate` | Calculate shipping price |
| POST | `/shipments` | Create a new shipment |
| GET | `/shipments/{reference}/track` | Track a shipment |

---

## Calculate Shipping Price

Calculate the delivery cost before creating a shipment.

### Request

```http
POST /v1/shipments/calculate
Content-Type: application/json
```

### Request Body

```json
{
  "pickup_type": "office",
  "pickup_office_id": 101,
  "delivery_type": "address",
  "delivery_city_id": 2,
  "service_type": "standard",
  "packages": [
    {
      "weight": 1.5,
      "length": 30,
      "width": 20,
      "height": 15
    }
  ],
  "cod_amount": 50.00
}
```

### Parameters

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `pickup_type` | string | Yes | `office` or `address` |
| `pickup_office_id` | integer | Conditional | Required if `pickup_type` is `office` |
| `delivery_type` | string | Yes | `address`, `office`, or `locker` |
| `delivery_office_id` | integer | Conditional | Required if `delivery_type` is `office` or `locker` |
| `delivery_city_id` | integer | Conditional | Required if `delivery_type` is `address` |
| `service_type` | string | Yes | `standard` or `express` |
| `packages` | array | Yes | Array of package objects |
| `cod_amount` | float | No | Cash on delivery amount |

### Package Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `weight` | float | Yes | Weight in kg |
| `length` | float | Yes | Length in cm |
| `width` | float | Yes | Width in cm |
| `height` | float | Yes | Height in cm |

### Response

> **Note**: The API returns `shipping_price` and `total_price` (not `delivery_fee` and `total` as originally documented).

```json
{
  "success": true,
  "data": {
    "shipping_price": 5.99,
    "total_price": 7.49,
    "cod_fee": 1.50,
    "expected_delivery_days": 2,
    "service_fees": [
      {
        "service_name": "Стандартна доставка",
        "fee": 5.99
      },
      {
        "service_name": "Наложен платеж",
        "fee": 1.50
      }
    ]
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `shipping_price` | float | Base delivery fee |
| `total_price` | float | Total including all fees |
| `cod_fee` | float\|null | COD handling fee (if applicable) |
| `expected_delivery_days` | integer | Estimated delivery time |
| `service_fees` | array | Breakdown of all fees |

---

## Create Shipment

Create a new shipment and get tracking information.

### Request

```http
POST /v1/shipments
Content-Type: application/json
```

### Request Body

```json
{
  "receiver_name": "Иван Петров",
  "receiver_phone": "+359888123456",
  "receiver_email": "ivan@example.com",
  "pickup_type": "office",
  "pickup_office_id": 101,
  "delivery_type": "address",
  "delivery_address": {
    "city_id": 2,
    "street_id": 5001,
    "street_number": "42",
    "building_number": "A",
    "entrance": "1",
    "floor": "3",
    "apartment": "12"
  },
  "service_type": "standard",
  "who_pays": "receiver",
  "packages": [
    {
      "weight": 1.5,
      "length": 30,
      "width": 20,
      "height": 15,
      "description": "Книги",
      "is_fragile": false
    }
  ],
  "cod_amount": 45.00,
  "sms_notification": true,
  "note": "Моля, обадете се преди доставка"
}
```

### Parameters

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `receiver_name` | string | Yes | Recipient's full name |
| `receiver_phone` | string | Yes | Recipient's phone number |
| `receiver_email` | string | No | Recipient's email |
| `pickup_type` | string | Yes | `office` or `address` |
| `pickup_office_id` | integer | Conditional | Required if `pickup_type` is `office` |
| `delivery_type` | string | Yes | `address`, `office`, or `locker` |
| `delivery_office_id` | integer | Conditional | Required if `delivery_type` is `office` or `locker` |
| `delivery_address` | object | Conditional | Required if `delivery_type` is `address` |
| `service_type` | string | Yes | `standard` or `express` |
| `who_pays` | string | Yes | `sender` or `receiver` |
| `packages` | array | Yes | Array of package objects |
| `cod_amount` | float | No | Cash on delivery amount (product value only) |
| `sms_notification` | boolean | No | Send SMS updates to receiver |
| `note` | string | No | Note for the courier |

### Delivery Address Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `city_id` | integer | Yes | City ID |
| `street_id` | integer | No | Street ID (if available) |
| `street_number` | string | No | Street/house number |
| `building_number` | string | No | Building number |
| `entrance` | string | No | Entrance number |
| `floor` | string | No | Floor number |
| `apartment` | string | No | Apartment number |
| `note` | string | No | Additional address info |

### Response

```json
{
  "success": true,
  "data": {
    "id": 12345,
    "reference_number": "PGN-2026-00012345",
    "status": "pending",
    "created_at": "2026-02-18T10:30:00Z",
    "packages": [
      {
        "id": 1,
        "tracking_number": "PGN1234567890",
        "weight": 1.5,
        "status": "pending"
      }
    ],
    "label_url": "https://api.pigeonexpress.com/labels/PGN-2026-00012345.pdf"
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Shipment ID |
| `reference_number` | string | Unique reference (e.g., "PGN-2026-00012345") |
| `status` | string | Current shipment status |
| `created_at` | string | Creation timestamp (ISO 8601) |
| `packages` | array | Package details with tracking numbers |
| `label_url` | string | URL to download shipping label PDF |

---

## Track Shipment

Get tracking information for a shipment.

### Request

```http
GET /v1/shipments/{reference}/track
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `reference` | string | Yes | Reference number (e.g., "PGN-2026-00012345") |

### Response

```json
{
  "success": true,
  "data": {
    "reference_number": "PGN-2026-00012345",
    "status": "in_transit",
    "status_description": "Пратката е в процес на доставка",
    "estimated_delivery": "2026-02-20",
    "events": [
      {
        "timestamp": "2026-02-18T14:30:00Z",
        "status": "in_transit",
        "description": "Пратката е изпратена от София",
        "location": "София, Сортировъчен център"
      },
      {
        "timestamp": "2026-02-18T10:30:00Z",
        "status": "picked_up",
        "description": "Пратката е приета",
        "location": "София Център"
      },
      {
        "timestamp": "2026-02-18T10:00:00Z",
        "status": "pending",
        "description": "Пратката е регистрирана",
        "location": null
      }
    ]
  }
}
```

---

## Shipment Statuses

| Status | Description |
|--------|-------------|
| `pending` | Shipment created, awaiting pickup |
| `picked_up` | Package picked up from sender |
| `in_transit` | Package in transit |
| `out_for_delivery` | Package out for delivery |
| `delivered` | Successfully delivered |
| `failed_delivery` | Delivery attempt failed |
| `returned` | Package returned to sender |
| `cancelled` | Shipment cancelled |

---

## SDK Usage

### Calculate Price

```php
<?php

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

$client = new PigeonExpress($apiKey, $apiSecret);

$request = new CalculateShipmentRequest('office', 'address', 'standard');
$request->setPickupOfficeId(101);
$request->addPackage(new PackageData(1.5, 30, 20, 15));
$request->setCodAmount(50.00);

$calculation = $client->shipments()->calculate($request);

echo "Delivery fee: " . $calculation->getDeliveryFee() . " BGN\n";
echo "Total: " . $calculation->getTotal() . " BGN\n";
echo "Expected delivery: " . $calculation->getExpectedDeliveryDays() . " days\n";
```

### Create Shipment

```php
<?php

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;
use PigeonExpress\DTO\Request\AddressData;

$client = new PigeonExpress($apiKey, $apiSecret);

$request = new CreateShipmentRequest(
    'Иван Петров',           // receiver_name
    '+359888123456',         // receiver_phone
    'office',                // pickup_type
    'address',               // delivery_type
    'standard',              // service_type
    'receiver'               // who_pays
);

$request->setReceiverEmail('ivan@example.com');
$request->setPickupOfficeId(101);

// Set delivery address
$address = new AddressData(2); // city_id = 2 (Plovdiv)
$address->setStreetId(5001);
$address->setStreetNumber('42');
$address->setBuildingNumber('A');
$address->setEntrance('1');
$address->setFloor('3');
$address->setApartment('12');
$request->setDeliveryAddress($address);

// Add package
$package = new PackageData(1.5, 30, 20, 15);
$package->setDescription('Книги');
$request->addPackage($package);

// Set COD (product value only, without delivery fee)
$request->setCodAmount(45.00);

// Optional settings
$request->enableSmsNotification();
$request->setNote('Моля, обадете се преди доставка');

// Create shipment
$shipment = $client->shipments()->create($request);

echo "Reference: " . $shipment->getReferenceNumber() . "\n";
echo "Tracking: " . $shipment->getFirstPackage()->getTrackingNumber() . "\n";
echo "Label: " . $shipment->getLabelUrl() . "\n";
```

### Track Shipment

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress($apiKey, $apiSecret);

$tracking = $client->shipments()->track('PGN-2026-00012345');

echo "Status: " . $tracking->getStatus() . "\n";
echo "Description: " . $tracking->getStatusDescription() . "\n";

foreach ($tracking->getEvents() as $event) {
    echo sprintf(
        "[%s] %s - %s\n",
        $event->getTimestamp()->format('Y-m-d H:i'),
        $event->getStatus(),
        $event->getDescription()
    );
}
```

---

## Important Notes

1. **COD Amount**: Should be the product value only, without delivery fee. Delivery fee is calculated separately.

2. **SMS Notification**: Required for locker deliveries to send pickup code.

3. **Who Pays**:
   - `sender` - Delivery fee added to your invoice
   - `receiver` - Delivery fee collected from recipient (usually with COD)

4. **API Response Fields**: The API uses `shipping_price`/`total_price` instead of `delivery_fee`/`total`. The SDK normalizes these automatically.
