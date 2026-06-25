<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Street DTO
 *
 * Represents a street from the API.
 */
class Street implements Arrayable
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
     * @var string
     */
    private $fullName;

    /**
     * @var int
     */
    private $cityId;

    /**
     * Get street ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get street name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get street type (e.g., "бул.", "ул.", "пл.")
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get full name (e.g., "бул. Цар Освободител")
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Get city ID
     *
     * @return int
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $street = new self();
        $street->id = isset($data['id']) ? (int) $data['id'] : 0;
        $street->name = isset($data['name']) ? $data['name'] : '';
        $street->type = isset($data['type']) ? $data['type'] : '';
        $street->fullName = isset($data['full_name']) ? $data['full_name'] : '';
        $street->cityId = isset($data['city_id']) ? (int) $data['city_id'] : 0;

        return $street;
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
            'full_name' => $this->fullName,
            'city_id' => $this->cityId,
        );
    }
}
