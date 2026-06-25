<?php

namespace PigeonExpress\Resources;

use PigeonExpress\Contracts\Arrayable;
use PigeonExpress\DTO\Request\SearchOfficesRequest;
use PigeonExpress\DTO\Response\Office;

/**
 * Offices Resource
 *
 * API resource for managing offices, lockers, and warehouses.
 */
class Offices extends AbstractResource
{
    /**
     * Get all offices
     *
     * @return Office[]
     */
    public function all()
    {
        $response = $this->getClient()->get('offices');

        return $this->mapToDto($response->getData('data'), Office::class);
    }

    /**
     * Search offices with filters
     *
     * @param array|SearchOfficesRequest $filters
     * @return Office[]
     */
    public function search($filters = array())
    {
        $query = $this->toArray($filters);
        $response = $this->getClient()->get('offices/search', $this->buildQuery($query));

        return $this->mapToDto($response->getData('data'), Office::class);
    }

    /**
     * Get offices by city ID
     *
     * @param int $cityId City ID
     * @param string|null $type Filter by type (office, locker, warehouse)
     * @return Office[]
     */
    public function byCity($cityId, $type = null)
    {
        $filters = array('city_id' => $cityId);

        if ($type !== null) {
            $filters['type'] = $type;
        }

        return $this->search($filters);
    }

    /**
     * Get offices by type
     *
     * @param string $type (office, locker, warehouse)
     * @return Office[]
     */
    public function byType($type)
    {
        return $this->search(array('type' => $type));
    }

    /**
     * Get all lockers
     *
     * @param array $filters Additional filters
     * @return Office[]
     */
    public function lockers(array $filters = array())
    {
        $filters['type'] = Office::TYPE_LOCKER;

        return $this->search($filters);
    }

    /**
     * Get all standard offices
     *
     * @param array $filters Additional filters
     * @return Office[]
     */
    public function offices(array $filters = array())
    {
        $filters['type'] = Office::TYPE_OFFICE;

        return $this->search($filters);
    }

    /**
     * Get all warehouses
     *
     * @param array $filters Additional filters
     * @return Office[]
     */
    public function warehouses(array $filters = array())
    {
        $filters['type'] = Office::TYPE_WAREHOUSE;

        return $this->search($filters);
    }

    /**
     * Search offices by name
     *
     * @param string $name
     * @param int|null $limit
     * @return Office[]
     */
    public function searchByName($name, $limit = null)
    {
        $filters = array('name' => $name);

        if ($limit !== null) {
            $filters['limit'] = $limit;
        }

        return $this->search($filters);
    }

    /**
     * Search offices by city name
     *
     * @param string $cityName
     * @param string|null $type
     * @return Office[]
     */
    public function searchByCity($cityName, $type = null)
    {
        $filters = array('city' => $cityName);

        if ($type !== null) {
            $filters['type'] = $type;
        }

        return $this->search($filters);
    }
}
