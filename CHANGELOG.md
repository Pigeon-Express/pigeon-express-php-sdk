# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-17

### Added
- Initial release of Pigeon Express PHP SDK
- Support for PHP 7.3+
- Guzzle HTTP client integration (6.5+ and 7.x)

### Resources
- **Offices** - List, search, filter by type/city
- **Cities** - List all Bulgarian cities, search, find by ID/name/postal code
- **Streets** - List by city, search
- **Shipments** - Calculate price, create shipment, track shipment

### Features
- Fluent interface for request building
- Type-safe DTO classes for requests and responses
- Comprehensive exception hierarchy for error handling
- Support for all shipment types (office, address, locker)
- Support for all service options (SMS, signature, review, etc.)
- Full validation with detailed error messages

### DTOs
- Request: PackageData, AddressData, CreateShipmentRequest, CalculateShipmentRequest
- Response: Office, City, Street, Shipment, ShipmentCalculation, TrackingInfo, etc.

### Exceptions
- AuthenticationException (401)
- ForbiddenException (403)
- NotFoundException (404)
- ValidationException (422)
- RateLimitException (429)
- ServerException (500+)
- ConnectionException (network errors)
