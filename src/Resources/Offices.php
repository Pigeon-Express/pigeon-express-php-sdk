<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Request\SearchOfficesRequest;
use PigeonExpress\DTO\Response\Office;

/**
 * Offices Resource
 *
 * API resource for offices, lockers and warehouses.
 *
 * There is no /offices/search endpoint. Filtering is done with query parameters
 * on GET /offices, which accepts name, city_id, postal_code, type, page and
 * per_page. Every search method here maps onto that.
 *
 * The endpoint is paginated with a hard cap of 100 rows per page, so any method
 * that promises "all" walks the pages. Returning only the first page would
 * silently truncate a merchant's office list.
 */
class Offices extends AbstractResource
{
    /** What {@see nearest()} can ask for: both kinds mixed by distance, or one. */
    const NEAREST_ALL = 'all';
    const NEAREST_OFFICE = 'office';
    const NEAREST_LOCKER = 'locker';

    /** Most locations the API returns for one nearest search. */
    const NEAREST_LIMIT_MAX = 20;

    /** How many a nearest search returns unless asked for another number. */
    const NEAREST_LIMIT_DEFAULT = 5;

    /**
     * Get every office, following pagination.
     *
     * @param array $filters Optional name, city_id, postal_code or type filter
     * @return Office[]
     */
    public function all(array $filters = array())
    {
        return $this->mapToDto($this->paginate('offices', $filters), Office::class);
    }

    /**
     * Search offices with filters.
     *
     * @param array|SearchOfficesRequest $filters
     * @return Office[]
     */
    public function search($filters = array())
    {
        return $this->all($this->toArray($filters));
    }

    /**
     * Get offices by city ID.
     *
     * @param int $cityId City ID
     * @param string|null $type Filter by type (office, locker, warehouse)
     * @return Office[]
     */
    public function byCity($cityId, $type = null)
    {
        $filters = array('city_id' => (int) $cityId);

        if ($type !== null) {
            $filters['type'] = $type;
        }

        return $this->all($filters);
    }

    /**
     * Get offices by type.
     *
     * @param string $type (office, locker, warehouse)
     * @return Office[]
     */
    public function byType($type)
    {
        return $this->all(array('type' => $type));
    }

    /**
     * Get all lockers.
     *
     * @param array $filters Additional filters
     * @return Office[]
     */
    public function lockers(array $filters = array())
    {
        $filters['type'] = Office::TYPE_LOCKER;

        return $this->all($filters);
    }

    /**
     * Get all standard offices.
     *
     * @param array $filters Additional filters
     * @return Office[]
     */
    public function offices(array $filters = array())
    {
        $filters['type'] = Office::TYPE_OFFICE;

        return $this->all($filters);
    }

    /**
     * Get all warehouses.
     *
     * @param array $filters Additional filters
     * @return Office[]
     */
    public function warehouses(array $filters = array())
    {
        $filters['type'] = Office::TYPE_WAREHOUSE;

        return $this->all($filters);
    }

    /**
     * Search offices by name. The API match is case insensitive.
     *
     * @param string $name
     * @param int|null $limit Cap the number of results returned
     * @return Office[]
     */
    public function searchByName($name, $limit = null)
    {
        $offices = $this->all(array('name' => $name));

        return $limit !== null ? array_slice($offices, 0, (int) $limit) : $offices;
    }

    /**
     * Search offices by city name.
     *
     * The API filters by city_id, not by city name, so the city is resolved
     * first and the offices are then fetched for that city.
     *
     * @param string $cityName
     * @param string|null $type
     * @return Office[]
     */
    public function searchByCity($cityName, $type = null)
    {
        $response = $this->getClient()->get('cities', $this->buildQuery(array(
            'name'     => $cityName,
            'per_page' => 1,
        )));

        $cities = $response->getData('data');
        if (!is_array($cities) || empty($cities[0]['id'])) {
            return array();
        }

        return $this->byCity($cities[0]['id'], $type);
    }

    /**
     * Get a single office by its ID.
     *
     * @param int $officeId
     * @return Office|null
     */
    public function find($officeId)
    {
        $response = $this->getClient()->get('offices/' . (int) $officeId);
        $data = $response->getData('data');

        return is_array($data) && !empty($data) ? Office::fromArray($data) : null;
    }

    /**
     * The offices and parcel lockers closest to a point, nearest first
     * (GET /offices/nearest).
     *
     * Not limited to one settlement: from a village it gives the offices of
     * the towns around it. Locations without coordinates are never in the
     * answer (in the test environment that means no locker), and neither are
     * warehouses or temporarily blocked lockers. Each Office has the fields
     * of {@see all()} plus {@see Office::getDistanceKm()}, the straight-line
     * distance in km. Not paginated.
     *
     * @param float $latitude WGS 84, -90 to 90
     * @param float $longitude WGS 84, -180 to 180
     * @param string $type NEAREST_ALL (both, mixed by distance), NEAREST_OFFICE
     *                     or NEAREST_LOCKER; `aps` is taken for a locker
     * @param int $limit 1 to NEAREST_LIMIT_MAX, kept within those bounds
     * @return Office[]
     * @throws \InvalidArgumentException For coordinates out of range or an unknown type
     */
    public function nearest($latitude, $longitude, $type = self::NEAREST_ALL, $limit = self::NEAREST_LIMIT_DEFAULT)
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new \InvalidArgumentException('Coordinates must be numbers.');
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if (!is_finite($latitude) || !is_finite($longitude)
            || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180
        ) {
            throw new \InvalidArgumentException('Coordinates are out of range.');
        }

        $type = strtolower(trim((string) $type));
        if ($type === 'aps') {
            $type = self::NEAREST_LOCKER;
        }

        if (!in_array($type, array(self::NEAREST_ALL, self::NEAREST_OFFICE, self::NEAREST_LOCKER), true)) {
            throw new \InvalidArgumentException(sprintf('Unknown location type: %s', $type));
        }

        $response = $this->getClient()->get('offices/nearest', array(
            // Fixed notation: a float cast to string can come out as 1.0E-5,
            // which the API does not read as a number.
            'latitude' => sprintf('%.6F', $latitude),
            'longitude' => sprintf('%.6F', $longitude),
            'type' => $type,
            'limit' => max(1, min(self::NEAREST_LIMIT_MAX, (int) $limit)),
        ));

        $data = $response->getData('data');
        $offices = array();

        foreach (is_array($data) ? $data : array() as $row) {
            if (is_array($row)) {
                $offices[] = Office::fromArray($row);
            }
        }

        return $offices;
    }
}
