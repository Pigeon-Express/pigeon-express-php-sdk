# Offices API

## Overview

The Offices API allows you to search and retrieve Pigeon Express offices and automated lockers. These are used as pickup and delivery points for shipments.

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/offices` | List all offices |
| GET | `/offices/{id}` | Get office by ID |
| GET | `/offices/search` | Search offices |

---

## List All Offices

Retrieves all active offices and lockers.

### Request

```http
GET /v1/offices
```

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "city_id": 1,
      "name": "София Център",
      "code": "SOF-001",
      "type": "office",
      "address": "гр. София, ул. Витоша 15",
      "latitude": 42.6977,
      "longitude": 23.3219,
      "working_hours": {
        "is_24_hours": false,
        "monday": "09:00-18:00",
        "tuesday": "09:00-18:00",
        "wednesday": "09:00-18:00",
        "thursday": "09:00-18:00",
        "friday": "09:00-18:00",
        "saturday": "10:00-14:00",
        "sunday": null
      },
      "is_active": true
    },
    {
      "id": 102,
      "city_id": 1,
      "name": "АПС Мол София",
      "code": "SOF-L01",
      "type": "locker",
      "address": "гр. София, бул. Цариградско шосе 115",
      "latitude": 42.6650,
      "longitude": 23.3789,
      "working_hours": {
        "is_24_hours": true
      },
      "is_active": true
    }
  ]
}
```

---

## Get Office by ID

Retrieves a specific office or locker by ID.

### Request

```http
GET /v1/offices/{id}
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Office ID |

### Response

```json
{
  "success": true,
  "data": {
    "id": 101,
    "city_id": 1,
    "name": "София Център",
    "code": "SOF-001",
    "type": "office",
    "address": "гр. София, ул. Витоша 15",
    "latitude": 42.6977,
    "longitude": 23.3219,
    "working_hours": {
      "is_24_hours": false,
      "monday": "09:00-18:00",
      "tuesday": "09:00-18:00",
      "wednesday": "09:00-18:00",
      "thursday": "09:00-18:00",
      "friday": "09:00-18:00",
      "saturday": "10:00-14:00",
      "sunday": null
    },
    "is_active": true
  }
}
```

---

## Search Offices

Search offices by city, type, or name.

### Request

```http
GET /v1/offices/search?city_id={cityId}&type={type}&query={query}&limit={limit}
```

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `city_id` | integer | No | - | Filter by city ID |
| `type` | string | No | - | Filter by type: `office` or `locker` |
| `query` | string | No | - | Search by name or address |
| `limit` | integer | No | 50 | Maximum results (1-100) |

### Examples

```http
# Get all offices in Sofia
GET /v1/offices/search?city_id=1&type=office

# Get all lockers in Plovdiv
GET /v1/offices/search?city_id=2&type=locker

# Search by name
GET /v1/offices/search?query=Мол&limit=10
```

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "city_id": 1,
      "name": "София Център",
      "code": "SOF-001",
      "type": "office",
      "address": "гр. София, ул. Витоша 15",
      "latitude": 42.6977,
      "longitude": 23.3219,
      "working_hours": { ... },
      "is_active": true
    }
  ]
}
```

---

## Office Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique office identifier |
| `city_id` | integer | City ID where office is located |
| `name` | string | Office name |
| `code` | string | Internal office code |
| `type` | string | `office` or `locker` |
| `address` | string | Full address |
| `latitude` | float\|null | GPS latitude |
| `longitude` | float\|null | GPS longitude |
| `working_hours` | object | Working hours schedule |
| `is_active` | boolean | Whether office is currently active |

---

## Working Hours Object

| Field | Type | Description |
|-------|------|-------------|
| `is_24_hours` | boolean | True if open 24/7 (usually lockers) |
| `monday` | string\|null | Hours for Monday (e.g., "09:00-18:00") |
| `tuesday` | string\|null | Hours for Tuesday |
| `wednesday` | string\|null | Hours for Wednesday |
| `thursday` | string\|null | Hours for Thursday |
| `friday` | string\|null | Hours for Friday |
| `saturday` | string\|null | Hours for Saturday |
| `sunday` | string\|null | Hours for Sunday |

> **Note**: `null` means closed on that day.

---

## Office Types

| Value | Description |
|-------|-------------|
| `office` | Staffed Pigeon Express office |
| `locker` | Automated parcel locker (usually 24/7) |

---

## SDK Usage

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress($apiKey, $apiSecret);

// Get all offices
$offices = $client->offices()->all();

foreach ($offices as $office) {
    echo $office->getId() . ': ' . $office->getName() . "\n";
    echo '  Type: ' . $office->getType() . "\n";
    echo '  Address: ' . $office->getAddress() . "\n";

    if ($office->getWorkingHours()) {
        $wh = $office->getWorkingHours();
        if ($wh->is24Hours()) {
            echo "  Open 24/7\n";
        } else {
            echo "  Monday: " . $wh->getMonday() . "\n";
        }
    }
}

// Get single office
$office = $client->offices()->find(101);
echo $office->getName(); // "София Център"

// Search offices in a city
$sofiaOffices = $client->offices()->search([
    'city_id' => 1,
    'type' => 'office',
]);

// Search lockers
$lockers = $client->offices()->search([
    'type' => 'locker',
    'query' => 'Мол',
]);
```

---

## Notes

- Office `city_id` is returned directly (not as a nested city object)
- Lockers are typically available 24/7 and require SMS notification for pickup
- Working hours may vary on holidays
- Use `is_active` to filter out temporarily closed locations
