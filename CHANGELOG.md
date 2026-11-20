# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `shipments()->get()` reads a full shipment from `GET /shipments/{ref}` as a
  `ShipmentDetails`, including the fees the carrier charged (`pricing.fees`).
- `shipments()->update()` edits a newly registered shipment with
  `PUT /shipments/{ref}`.
- `shipments()->trackBulk()` tracks any number of shipments through
  `POST /shipments/track/bulk`, 100 per request, as `BulkTrackingEntry` objects.
- `shipmentStatuses()` resource for `GET /shipment-statuses`.
- `courierRequests()` resource to create, read back and cancel courier
  requests. The API has no list of them.
- `addresses()` resource for the account's saved addresses (`GET /addresses`).
- `ConflictException` for HTTP 409, carrying `Retry-After`. Shipment create and
  update and the courier request calls repeat a 409 up to twice with the same
  body, and `getLastReplay()` tells when the answer was a replay of an earlier
  identical request (the `X-Duplicate-Request` header, also on
  `Response::isDuplicateRequest()`).
- Create request fields: reverse shipments and their sender, saved address ids,
  return office or address, return documents destination,
  `external_reference` (checked to be at most 100 characters),
  `hide_content_on_label`, `label_format`, `pay_with_pos`, `service_codes`
  and `inventory_items`.
- `CreateShipmentRequest::fromArray()` and `set()` on the create request,
  package and address objects, so fields without a setter are sent instead of
  dropped.
- `FeeBreakdown` exposes the fee's service `code` and `quantity`.
- `offices()->nearest($latitude, $longitude, $type, $limit)` for
  `GET /offices/nearest`: the offices and parcel lockers closest to a point,
  nearest first, with `Office::getDistanceKm()`. `$type` is `all`, `office`
  or `locker` (`aps` is taken for a locker), `$limit` 1 to 20 (default 5).
  Coordinates are sent in fixed notation; a point out of range or an unknown
  type throws `InvalidArgumentException` without a request.
- `lockers()` resource: `availability($code)` reads a parcel locker's box
  sizes and the forecast of free boxes for today and tomorrow from
  `GET /lockers/{code}/availability` as a `LockerAvailability`
  (`getBoxSizes()` as `LockerBoxSize`, `getFreeBoxes('today'|'tomorrow')`,
  `isBlocked()`, `getBlockedUntil()`).
- `shipments()->actions($ref)` reads the sender's quick actions from
  `GET /shipments/{ref}/actions` as a `ShipmentActions` (all four actions
  always present, with `isAllowed()`, `getReasonCode()`, `getReason()`, the
  current values, the services that can be added or removed and the active
  reference number of a redirected shipment).
- `shipments()->runAction($ref, $action, $body, $dryRun)` takes or prices one
  quick action through `POST /shipments/{ref}/actions/{action}` and returns a
  `ShipmentActionResult` (price, removed services, the new reference number
  of a redirect). Either spelling of the action is accepted
  (`change_delivery` or `change-delivery`); `dry_run` is always sent as a
  boolean from `$dryRun`. A real action repeats a 409 like an edit and
  reports a replay through `getLastReplay()`; a dry run is never repeated.
- Streets: `byCity()`, `searchInCity()` and `search()` take an optional
  postcode (`postal_code`, a prefix match, sent only when 2 to 10
  characters), and `Street::getPostalCode()` reads the street's postcode.
- Every exception for an API error answer keeps the decoded body:
  `getResponseData()`, `getErrorCode()` (the API's `code`, for example
  `requires_office_payment`, `already_redirected`, `not_found` or
  `backend_unavailable`) and `getResponseField('price.amount_due')`, next to
  the HTTP status from `getHttpStatusCode()`.

### Fixed
- `track()` URL-encodes the reference number.
- An error answered in the `{"error": {"message": ...}}` shape (an unknown
  route) keeps its message instead of "Unknown error".
- `Shipment` reads the status the create endpoint sends as a plain name.
- `WorkingHours` reads the list of `{day, open, close}` entries the offices
  endpoint sends. It expected an object keyed by day and lost every day.
  `getEntries()` returns each stretch as listed, lunch breaks included.

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
