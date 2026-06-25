<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\City;

/**
 * Cities Resource
 *
 * API resource for managing cities.
 */
class Cities extends AbstractResource
{
    /**
     * Get all cities
     *
     * @return City[]
     */
    public function all()
    {
        $response = $this->getClient()->get('cities');

        return $this->mapToDto($response->getData('data'), City::class);
    }

    /**
     * Find city by ID
     *
     * @param int $id City ID
     * @return City|null
     */
    public function find($id)
    {
        $cities = $this->all();

        foreach ($cities as $city) {
            if ($city->getId() === (int) $id) {
                return $city;
            }
        }

        return null;
    }

    /**
     * Search cities by name
     *
     * @param string $name City name to search
     * @return City[]
     */
    public function search($name)
    {
        $cities = $this->all();
        $results = array();
        $searchLower = mb_strtolower($name);

        foreach ($cities as $city) {
            $cityNameLower = mb_strtolower($city->getName());

            if (strpos($cityNameLower, $searchLower) !== false) {
                $results[] = $city;
            }
        }

        return $results;
    }

    /**
     * Find city by exact name
     *
     * @param string $name Exact city name
     * @return City|null
     */
    public function findByName($name)
    {
        $cities = $this->all();
        $searchLower = mb_strtolower($name);

        foreach ($cities as $city) {
            if (mb_strtolower($city->getName()) === $searchLower) {
                return $city;
            }
        }

        return null;
    }

    /**
     * Get cities by district
     *
     * @param string $district District name
     * @return City[]
     */
    public function byDistrict($district)
    {
        $cities = $this->all();
        $results = array();
        $districtLower = mb_strtolower($district);

        foreach ($cities as $city) {
            $cityDistrict = $city->getDistrict();

            if ($cityDistrict !== null && mb_strtolower($cityDistrict) === $districtLower) {
                $results[] = $city;
            }
        }

        return $results;
    }

    /**
     * Find city by postal code
     *
     * @param string $postalCode
     * @return City|null
     */
    public function findByPostalCode($postalCode)
    {
        $cities = $this->all();

        foreach ($cities as $city) {
            if ($city->getPostalCode() === $postalCode) {
                return $city;
            }
        }

        return null;
    }
}
