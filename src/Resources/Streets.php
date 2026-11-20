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
 * characters, with Latin to Cyrillic transliteration), a postal_code filter
 * (a prefix match, 2 to 10 characters) plus page and per_page. Every method
 * here maps onto that.
 */
class Streets extends AbstractResource
{
    /** The API requires at least this many characters for a name search. */
    const MIN_SEARCH_LENGTH = 2;

    /** The postcode filter the API accepts: 2 to 10 characters. */
    const POSTAL_CODE_MIN_LENGTH = 2;
    const POSTAL_CODE_MAX_LENGTH = 10;

    /**
     * Get every street in a city, following pagination.
     *
     * A postcode is a prefix match on the API's side ("4000" also returns
     * streets under "40001"), and streets the API has no postcode for are
     * left out, so a caller that finds nothing that way tries again without
     * it. A postcode shorter than 2 or longer than 10 characters is not sent.
     *
     * @param int $cityId City ID
     * @param string|null $name Optional name filter, applied by the API
     * @param string|null $postalCode Optional postcode filter, applied by the API
     * @return Street[]
     */
    public function byCity($cityId, $name = null, $postalCode = null)
    {
        return $this->mapToDto($this->fetchAllPages($cityId, $name, $postalCode), Street::class);
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

        $streets = $this->byCity(
            $query['city_id'],
            isset($query['name']) ? $query['name'] : null,
            isset($query['postal_code']) ? $query['postal_code'] : null
        );

        return $this->applyLimit($streets, $query);
    }

    /**
     * Search streets by name within a city.
     *
     * @param int $cityId City ID
     * @param string $name Street name to search
     * @param int|null $limit Cap the number of results returned
     * @param string|null $postalCode Optional postcode filter, see byCity()
     * @return Street[]
     */
    public function searchInCity($cityId, $name, $limit = null, $postalCode = null)
    {
        $streets = $this->byCity($cityId, $name, $postalCode);

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
     * @param string|null $postalCode
     * @return array
     */
    private function fetchAllPages($cityId, $name = null, $postalCode = null)
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

        $postalCode = is_scalar($postalCode) ? trim((string) $postalCode) : '';
        $length = mb_strlen($postalCode);
        // Outside what the API accepts the filter is refused, so it is left
        // out and the listing is narrowed by name only.
        if ($length >= self::POSTAL_CODE_MIN_LENGTH && $length <= self::POSTAL_CODE_MAX_LENGTH) {
            $filters['postal_code'] = $postalCode;
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
