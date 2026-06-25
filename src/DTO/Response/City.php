<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * City DTO
 *
 * Represents a city from the API.
 */
class City implements Arrayable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $district;

    /**
     * @var string|null
     */
    private $municipality;

    /**
     * @var string|null
     */
    private $postalCode;

    /**
     * Get city ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get city name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get city type (e.g., "гр.", "с.")
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get district
     *
     * @return string|null
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Get municipality
     *
     * @return string|null
     */
    public function getMunicipality()
    {
        return $this->municipality;
    }

    /**
     * Get postal code
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Get full name with type (e.g., "гр. София")
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->type . ' ' . $this->name;
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $city = new self();
        $city->id = isset($data['id']) ? (int) $data['id'] : 0;
        $city->name = isset($data['name']) ? $data['name'] : '';
        $city->type = isset($data['type']) ? $data['type'] : '';
        $city->district = isset($data['district']) ? $data['district'] : null;
        $city->municipality = isset($data['municipality']) ? $data['municipality'] : null;
        $city->postalCode = isset($data['postal_code']) ? $data['postal_code'] : null;

        return $city;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'district' => $this->district,
            'municipality' => $this->municipality,
            'postal_code' => $this->postalCode,
        );
    }
}
