# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-30

### Fixed
- `ShipmentCalculation` now reads `total_service_fees`, `currency`, `service_type`
  and the applied `service_codes`. It previously dropped them and instead modelled
  fields the API does not return, so callers could not see what additional
  services actually cost.
- Office and street search no longer call `/offices/search` and `/streets/search`,
  which do not exist and returned 404. All eleven affected methods now use the
  documented query parameters on `GET /offices` and `GET /cities/{id}/streets`.
  Method signatures are unchanged.
- Listing methods that promise every record now follow pagination instead of
  silently returning only the first 100 rows. This affected offices, lockers and
  cities, where it could quietly truncate a merchant's data.
- `Cities::find()` fetches a single city by ID rather than listing every city.
- `City` and `Street` now expose the Latin transliterations the API returns
  (`name_en` and friends), needed to match input typed in Latin script.

### Added
- `additionalServices()` resource for `GET /additional-services`, with an
  `AdditionalService` DTO. Note the endpoint returns service definitions only and
  never prices.
- Paginated listings retry a failed page and obey `Retry-After` on a rate limit,
  rather than abandoning the whole walk.
- Unit test suite covering DTO mapping, pagination and retry behaviour.

### Changed
- `Streets::searchByName()` now throws a descriptive exception instead of a 404.
  Streets are only addressable within a city, so use `searchInCity()`.
- `Streets::search()` requires a `city_id` for the same reason.

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
