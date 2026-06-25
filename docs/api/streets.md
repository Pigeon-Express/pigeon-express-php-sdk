# Streets API

## Overview

The Streets API allows you to retrieve and search streets within a specific city. Streets are used for address delivery to provide accurate location data.

> **Note**: Not all cities have street-level data. Check the `has_streets` field on the city object before querying streets.

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cities/{cityId}/streets` | List streets in a city |
| GET | `/cities/{cityId}/streets/search` | Search streets in a city |

---

## List Streets by City

Retrieves all streets for a specific city.

### Request

```http
GET /v1/cities/{cityId}/streets
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cityId` | integer | Yes | City ID |

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 10001,
      "name": "Александър Стамболийски",
      "name_en": "Alexander Stamboliyski",
      "type": "булевард"
    },
    {
      "id": 10002,
      "name": "Витоша",
      "name_en": "Vitosha",
      "type": "булевард"
    },
    {
      "id": 10003,
      "name": "Христо Ботев",
      "name_en": "Hristo Botev",
      "type": "улица"
    }
  ]
}
```

---

## Search Streets

Search streets within a city by name.

### Request

```http
GET /v1/cities/{cityId}/streets/search?query={query}&limit={limit}
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `cityId` | integer | Yes | - | City ID |
| `query` | string | Yes | - | Search term |
| `limit` | integer | No | 10 | Maximum results (1-100) |

### Example

```http
GET /v1/cities/1/streets/search?query=Христо&limit=10
```

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 10003,
      "name": "Христо Ботев",
      "name_en": "Hristo Botev",
      "type": "улица"
    },
    {
      "id": 10004,
      "name": "Христо Смирненски",
      "name_en": "Hristo Smirnenski",
      "type": "улица"
    },
    {
      "id": 10005,
      "name": "Христо Кърджилов",
      "name_en": "Hristo Kardzhilov",
      "type": "улица"
    }
  ]
}
```

---

## Street Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique street identifier |
| `name` | string | Street name in Bulgarian |
| `name_en` | string\|null | Street name in English |
| `type` | string | Street type (see table below) |

---

## Street Types

| Value | Abbreviation | Description |
|-------|--------------|-------------|
| `улица` | ул. | Street |
| `ул` | ул. | Street (short form) |
| `булевард` | бул. | Boulevard |
| `площад` | пл. | Square |
| `алея` | ал. | Alley |
| `квартал` | кв. | Quarter/Neighborhood |
| `махала` | мах. | Small neighborhood |
| `местност` | м. | Area/Locality |
| `село` | с. | Village (within city) |
| `общежитие` | общ. | Dormitory |
| `` (empty) | ул. | Default to street |

---

## SDK Usage

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress($apiKey, $apiSecret);

// Get all streets for a city
$streets = $client->streets()->byCity(1); // Sofia = 1

foreach ($streets as $street) {
    echo $street->getId() . ': ';
    echo $street->getType() . ' ' . $street->getName() . "\n";
}

// Search streets
$results = $client->streets()->search(1, 'Витоша');

foreach ($results as $street) {
    echo $street->getName() . ' (' . $street->getType() . ")\n";
}
```

---

## Building Full Address

When creating a shipment, combine street data with additional details:

```php
<?php

use PigeonExpress\DTO\Request\AddressData;

// Create address with street
$address = new AddressData($cityId);
$address->setStreetId($streetId);
$address->setStreetNumber('42');
$address->setBuildingNumber('A');
$address->setEntrance('1');
$address->setFloor('3');
$address->setApartment('12');

// Full address example: ул. Христо Ботев 42, бл. A, вх. 1, ет. 3, ап. 12
```

---

## Notes

- Search is case-insensitive and supports Cyrillic characters
- Streets are synced on-demand when first queried for a city
- For cities without street data (`has_streets: false`), use free-text address
- Street types are returned in Bulgarian - use the mapping table for display
