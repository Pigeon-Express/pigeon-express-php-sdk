<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\City;

/**
 * Cities Resource
 *
 * API resource for cities.
 *
 * GET /cities is paginated with a hard cap of 100 rows per page and accepts a
 * name or postal_code filter. all() walks every page, because there are
 * thousands of cities and returning only the first hundred would silently
 * truncate a location sync.
 *
 * Where the API can filter server side, it does. Only find() and byDistrict()
 * still scan locally, the former because there is no by-id endpoint that avoids
 * a full listing being cheaper, the latter because district is not a supported
 * query parameter.
 */
class Cities extends AbstractResource
{
    /**
     * Get every city, following pagination.
     *
     * @param array $filters Optional name or postal_code filter
     * @return City[]
     */
    public function all(array $filters = array())
    {
        return $this->mapToDto($this->paginate('cities', $filters), City::class);
    }

    /**
     * Find city by ID.
     *
     * @param int $id City ID
     * @return City|null
     */
    public function find($id)
    {
        $response = $this->getClient()->get('cities/' . (int) $id);
        $data = $response->getData('data');

        return is_array($data) && !empty($data) ? City::fromArray($data) : null;
    }

    /**
     * Search cities by name. Filtered by the API rather than locally.
     *
     * @param string $name City name to search
     * @return City[]
     */
    public function search($name)
    {
        return $this->all(array('name' => $name));
    }

    /**
     * Find city by exact name.
     *
     * The API match is a partial, case insensitive one, so the exact comparison
     * still happens here, but only across the rows the API already narrowed.
     *
     * @param string $name Exact city name
     * @return City|null
     */
    public function findByName($name)
    {
        $searchLower = mb_strtolower($name);

        foreach ($this->all(array('name' => $name)) as $city) {
            if (mb_strtolower($city->getName()) === $searchLower) {
                return $city;
            }
        }

        return null;
    }

    /**
     * Get cities by district.
     *
     * District is not a supported query parameter, so this one genuinely has to
     * scan the full list.
     *
     * @param string $district District name
     * @return City[]
     */
    public function byDistrict($district)
    {
        $districtLower = mb_strtolower($district);
        $results = array();

        foreach ($this->all() as $city) {
            $cityDistrict = $city->getDistrict();

            if ($cityDistrict !== null && mb_strtolower($cityDistrict) === $districtLower) {
                $results[] = $city;
            }
        }

        return $results;
    }

    /**
     * Find city by postal code. Filtered by the API, which does a prefix match,
     * so the exact comparison is still made here.
     *
     * @param string $postalCode
     * @return City|null
     */
    public function findByPostalCode($postalCode)
    {
        foreach ($this->all(array('postal_code' => $postalCode)) as $city) {
            if ($city->getPostalCode() === $postalCode) {
                return $city;
            }
        }

        return null;
    }

}
