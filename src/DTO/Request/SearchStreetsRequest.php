<?php

namespace PigeonExpress\DTO\Request;

use PigeonExpress\Contracts\Arrayable;

/**
 * Search Streets Request DTO
 *
 * Filter parameters for searching streets.
 */
class SearchStreetsRequest implements Arrayable
{
    /**
     * @var string|null Search by street name
     */
    private $name;

    /**
     * @var int|null Filter by city ID
     */
    private $cityId;

    /**
     * @var string|null Filter by street type
     */
    private $type;

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
     * Set type filter
     *
     * @param string $type
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

        if ($this->type !== null) {
            $data['type'] = $this->type;
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

        if (isset($data['type'])) {
            $request->setType($data['type']);
        }

        if (isset($data['limit'])) {
            $request->setLimit($data['limit']);
        }

        return $request;
    }
}
