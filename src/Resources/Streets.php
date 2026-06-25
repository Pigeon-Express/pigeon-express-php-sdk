<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Request\SearchStreetsRequest;
use PigeonExpress\DTO\Response\Street;

/**
 * Streets Resource
 *
 * API resource for managing streets.
 */
class Streets extends AbstractResource
{
    /**
     * Get streets by city ID
     *
     * @param int $cityId City ID
     * @return Street[]
     */
    public function byCity($cityId)
    {
        $response = $this->getClient()->get('cities/' . (int) $cityId . '/streets');

        return $this->mapToDto($response->getData('data'), Street::class);
    }

    /**
     * Search streets with filters
     *
     * @param array|SearchStreetsRequest $filters
     * @return Street[]
     */
    public function search($filters = array())
    {
        $query = $this->toArray($filters);
        $response = $this->getClient()->get('streets/search', $this->buildQuery($query));

        return $this->mapToDto($response->getData('data'), Street::class);
    }

    /**
     * Search streets by name in a specific city
     *
     * @param int $cityId City ID
     * @param string $name Street name to search
     * @param int|null $limit Limit results
     * @return Street[]
     */
    public function searchInCity($cityId, $name, $limit = null)
    {
        $filters = array(
            'city_id' => $cityId,
            'name' => $name,
        );

        if ($limit !== null) {
            $filters['limit'] = $limit;
        }

        return $this->search($filters);
    }

    /**
     * Search streets by name (across all cities)
     *
     * @param string $name Street name to search
     * @param int|null $limit Limit results
     * @return Street[]
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
     * Find street by ID in a city
     *
     * @param int $cityId City ID
     * @param int $streetId Street ID
     * @return Street|null
     */
    public function find($cityId, $streetId)
    {
        $streets = $this->byCity($cityId);

        foreach ($streets as $street) {
            if ($street->getId() === (int) $streetId) {
                return $street;
            }
        }

        return null;
    }
}
