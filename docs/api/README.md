# Pigeon Express API Documentation

## Overview

Pigeon Express API provides a RESTful interface for managing courier shipments in Bulgaria. The API allows you to:

- **Locations**: Search cities, streets, and offices/lockers
- **Shipments**: Calculate prices, create shipments, and track deliveries
- **COD**: Handle cash on delivery payments

## Base URL

```
Production: https://api.pigeonexpress.com/v1
Sandbox:    https://api-demo.pigeonexpress.com/v1
```

## Authentication

All API requests require authentication using API Key and Secret. See [Authentication](authentication.md) for details.

## Response Format

All responses are returned in JSON format with the following structure:

### Success Response

```json
{
  "success": true,
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message"
  }
}
```

## API Endpoints

| Resource | Description | Documentation |
|----------|-------------|---------------|
| Cities | Search and list cities | [cities.md](cities.md) |
| Streets | Get streets for a city | [streets.md](streets.md) |
| Offices | Search offices and lockers | [offices.md](offices.md) |
| Shipments | Calculate, create, track | [shipments.md](shipments.md) |

## Rate Limiting

- **Standard**: 100 requests per minute
- **Burst**: 10 requests per second

Rate limit headers are included in all responses:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640000000
```

## Data Types

### Location Types (Bulgarian)

Cities and streets use Bulgarian type names:

| Type | Description | Prefix |
|------|-------------|--------|
| `град` | City | гр. |
| `село` | Village | с. |
| `к.к.` / `курорт` | Resort | к.к. |
| `улица` / `ул` | Street | ул. |
| `булевард` | Boulevard | бул. |
| `площад` | Square | пл. |
| `алея` | Alley | ал. |
| `квартал` | Quarter | кв. |
| `махала` | Neighborhood | мах. |
| `местност` | Area | м. |

### Delivery Types

| Value | Description |
|-------|-------------|
| `address` | Delivery to customer address |
| `office` | Delivery to Pigeon Express office |
| `locker` | Delivery to automated locker |

### Service Types

| Value | Description | Delivery Time |
|-------|-------------|---------------|
| `standard` | Standard delivery | 2-3 business days |
| `express` | Express delivery | Next business day |

### Who Pays

| Value | Description |
|-------|-------------|
| `sender` | Sender pays delivery fee |
| `receiver` | Receiver pays delivery fee |

## Versioning

The API is versioned via the URL path (`/v1/`). Breaking changes will result in a new version.

## Support

For API support, contact: api-support@pigeonexpress.bg
