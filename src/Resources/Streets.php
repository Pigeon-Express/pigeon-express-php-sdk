<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Request\SearchStreetsRequest;
use PigeonExpress\DTO\Response\Street;
use PigeonExpress\Exceptions\PigeonExpressException;

/**
 * Streets Resource
 *
 * API resource for streets.
 *
 * There is no /streets/search endpoint. Streets only exist within a city, via
 * GET /cities/{cityId}/streets, which accepts a name filter (minimum 2
 * characters, with Latin to Cyrillic transliteration) plus page and per_page.
 * Every method here maps onto that.
 */
class Streets extends AbstractResource
{
    /** The API requires at least this many characters for a name search. */
    const MIN_SEARCH_LENGTH = 2;

    /**
     * Get every street in a city, following pagination.
     *
     * @param int $cityId City ID
     * @param string|null $name Optional name filter, applied by the API
     * @return Street[]
     */
    public function byCity($cityId, $name = null)
    {
        return $this->mapToDto($this->fetchAllPages($cityId, $name), Street::class);
    }

    /**
     * Search streets with filters. A city_id is required.
     *
     * @param array|SearchStreetsRequest $filters
     * @return Street[]
     * @throws PigeonExpressException When no city_id is supplied
     */
    public function search($filters = array())
    {
        $query = $this->toArray($filters);

        if (empty($query['city_id'])) {
            throw new PigeonExpressException(
                'Street search requires a city_id. The Pigeon Express API only exposes streets '
                . 'within a city, via GET /cities/{cityId}/streets.'
            );
        }

        $streets = $this->byCity($query['city_id'], isset($query['name']) ? $query['name'] : null);

        return $this->applyLimit($streets, $query);
    }

    /**
     * Search streets by name within a city.
     *
     * @param int $cityId City ID
     * @param string $name Street name to search
     * @param int|null $limit Cap the number of results returned
     * @return Street[]
     */
    public function searchInCity($cityId, $name, $limit = null)
    {
        $streets = $this->byCity($cityId, $name);

        return $limit !== null ? array_slice($streets, 0, (int) $limit) : $streets;
    }

    /**
     * Search streets by name across all cities.
     *
     * Not supported by the API: streets are only addressable within a city.
     * Use searchInCity() instead.
     *
     * @param string $name Street name to search
     * @param int|null $limit
     * @return Street[]
     * @throws PigeonExpressException Always
     */
    public function searchByName($name, $limit = null)
    {
        throw new PigeonExpressException(
            'Searching streets across all cities is not supported by the Pigeon Express API. '
            . 'Use searchInCity($cityId, $name) instead.'
        );
    }

    /**
     * Find a street by ID within a city.
     *
     * @param int $cityId City ID
     * @param int $streetId Street ID
     * @return Street|null
     */
    public function find($cityId, $streetId)
    {
        $streetId = (int) $streetId;

        foreach ($this->byCity($cityId) as $street) {
            if ($street->getId() === $streetId) {
                return $street;
            }
        }

        return null;
    }

    /**
     * Build the filter set for a street listing and walk every page.
     *
     * @param int $cityId
     * @param string|null $name
     * @return array
     */
    private function fetchAllPages($cityId, $name = null)
    {
        $filters = array();

        if ($name !== null && $name !== '') {
            $name = (string) $name;
            // Below the API minimum the filter is rejected, so fetch unfiltered
            // and let the caller narrow it down.
            if (mb_strlen($name) >= self::MIN_SEARCH_LENGTH) {
                $filters['name'] = mb_substr($name, 0, 100);
            }
        }

        return $this->paginate('cities/' . (int) $cityId . '/streets', $filters);
    }

    /**
     * @param Street[] $streets
     * @param array $query
     * @return Street[]
     */
    private function applyLimit(array $streets, array $query)
    {
        if (isset($query['limit'])) {
            return array_slice($streets, 0, (int) $query['limit']);
        }

        return $streets;
    }
}
