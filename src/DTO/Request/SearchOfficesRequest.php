<?php

namespace PigeonExpress\DTO\Request;

use PigeonExpress\Contracts\Arrayable;

/**
 * Search Offices Request DTO
 *
 * Filter parameters for searching offices.
 */
class SearchOfficesRequest implements Arrayable
{
    /**
     * @var string|null Search by office name
     */
    private $name;

    /**
     * @var int|null Filter by city ID
     */
    private $cityId;

    /**
     * @var string|null Search by city name
     */
    private $city;

    /**
     * @var string|null Filter by type (office, locker, warehouse)
     */
    private $type;

    /**
     * @var string|null Filter by postal code
     */
    private $postalCode;

    /**
     * @var int|null Limit results
     */
    private $limit;

    /**
     * Set name filter
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name filter
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set city ID filter
     *
     * @param int $cityId
     * @return $this
     */
    public function setCityId($cityId)
    {
        $this->cityId = (int) $cityId;
        return $this;
    }

    /**
     * Get city ID filter
     *
     * @return int|null
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * Set city name filter
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get city name filter
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set type filter
     *
     * @param string $type (office, locker, warehouse)
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type filter
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set postal code filter
     *
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * Get postal code filter
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set results limit
     *
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Get results limit
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $data = array();

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->cityId !== null) {
            $data['city_id'] = $this->cityId;
        }

        if ($this->city !== null) {
            $data['city'] = $this->city;
        }

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->postalCode !== null) {
            $data['postal_code'] = $this->postalCode;
        }

        if ($this->limit !== null) {
            $data['limit'] = $this->limit;
        }

        return $data;
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $request = new self();

        if (isset($data['name'])) {
            $request->setName($data['name']);
        }

        if (isset($data['city_id'])) {
            $request->setCityId($data['city_id']);
        }

        if (isset($data['city'])) {
            $request->setCity($data['city']);
        }

        if (isset($data['type'])) {
            $request->setType($data['type']);
        }

        if (isset($data['postal_code'])) {
            $request->setPostalCode($data['postal_code']);
        }

        if (isset($data['limit'])) {
            $request->setLimit($data['limit']);
        }

        return $request;
    }
}
