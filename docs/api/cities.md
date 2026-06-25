# Cities API

## Overview

The Cities API allows you to search and retrieve cities in Bulgaria. Cities are used for address delivery and as references for streets and offices.

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cities` | List all cities |
| GET | `/cities/{id}` | Get city by ID |
| GET | `/cities/search` | Search cities by name |

---

## List All Cities

Retrieves all available cities.

### Request

```http
GET /v1/cities
```

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "София",
      "name_en": "Sofia",
      "type": "град",
      "municipality": "Столична",
      "district": "София-град",
      "postal_code": "1000",
      "has_streets": true
    },
    {
      "id": 2,
      "name": "Пловдив",
      "name_en": "Plovdiv",
      "type": "град",
      "municipality": "Пловдив",
      "district": "Пловдив",
      "postal_code": "4000",
      "has_streets": true
    }
  ]
}
```

---

## Get City by ID

Retrieves a specific city by its ID.

### Request

```http
GET /v1/cities/{id}
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | City ID |

### Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "София",
    "name_en": "Sofia",
    "type": "град",
    "municipality": "Столична",
    "district": "София-град",
    "postal_code": "1000",
    "has_streets": true
  }
}
```

---

## Search Cities

Search cities by name (supports partial matching).

### Request

```http
GET /v1/cities/search?query={query}&limit={limit}
```

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `query` | string | Yes | - | Search term (min 2 characters) |
| `limit` | integer | No | 10 | Maximum results (1-100) |

### Example

```http
GET /v1/cities/search?query=Абланица&limit=10
```

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "name": "Абланица",
      "name_en": "Ablanitsa",
      "type": "село",
      "municipality": "Велинград",
      "district": "Пазарджик",
      "postal_code": "4632",
      "has_streets": false
    },
    {
      "id": 12346,
      "name": "Абланица",
      "name_en": "Ablanitsa",
      "type": "село",
      "municipality": "Хаджидимово",
      "district": "Благоевград",
      "postal_code": "2931",
      "has_streets": false
    },
    {
      "id": 12347,
      "name": "Абланица",
      "name_en": "Ablanitsa",
      "type": "село",
      "municipality": "Ловеч",
      "district": "Ловеч",
      "postal_code": "5561",
      "has_streets": false
    }
  ]
}
```

---

## City Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique city identifier |
| `name` | string | City name in Bulgarian |
| `name_en` | string\|null | City name in English |
| `type` | string | City type: `град`, `село`, `к.к.`, `курорт` |
| `municipality` | string | Municipality (община) |
| `district` | string | District (област) |
| `postal_code` | string | Postal code |
| `has_streets` | boolean | Whether city has street-level data |

---

## City Types

| Value | Bulgarian | Description |
|-------|-----------|-------------|
| `град` | Град | City |
| `село` | Село | Village |
| `к.к.` | Курортен комплекс | Resort complex |
| `курорт` | Курорт | Resort |

---

## SDK Usage

```php
<?php

use PigeonExpress\PigeonExpress;

$client = new PigeonExpress($apiKey, $apiSecret);

// Get all cities
$cities = $client->cities()->all();

foreach ($cities as $city) {
    echo $city->getId() . ': ' . $city->getName() . "\n";
    echo '  Type: ' . $city->getType() . "\n";
    echo '  Municipality: ' . $city->getMunicipality() . "\n";
}

// Get single city
$city = $client->cities()->find(1);
echo $city->getName(); // "София"

// Search cities
$results = $client->cities()->search('Пловдив');
foreach ($results as $city) {
    echo $city->getName() . ', ' . $city->getMunicipality() . "\n";
}
```

---

## Notes

- Search is case-insensitive and supports Cyrillic characters
- The `has_streets` field indicates if you can query streets for this city
- For villages without street data, use free-text address field
- Multiple cities can have the same name - use `municipality` to differentiate
