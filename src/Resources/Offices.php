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

}
