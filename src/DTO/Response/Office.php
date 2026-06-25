<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Office DTO
 *
 * Represents an office, locker, or warehouse from the API.
 */
class Office implements Arrayable
{
    /**
     * Office types
     */
    const TYPE_OFFICE = 'office';
    const TYPE_LOCKER = 'locker';
    const TYPE_WAREHOUSE = 'warehouse';

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
    private $code;

    /**
     * @var string
     */
    private $type;

    /**
     * @var City|null
     */
    private $city;

    /**
     * @var int|null City ID (when city object is not available)
     */
    private $cityId;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string|null
     */
    private $phone;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var WorkingHours|null
     */
    private $workingHours;

    /**
     * @var float|null
     */
    private $latitude;

    /**
     * @var float|null
     */
    private $longitude;

    /**
     * Get office ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get office name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get office code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get office type (office, locker, warehouse)
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get city
     *
     * @return City|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Get city ID (from city object or direct field)
     *
     * @return int|null
     */
    public function getCityId()
    {
        if ($this->city !== null) {
            return $this->city->getId();
        }
        return $this->cityId;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Get phone
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get working hours
     *
     * @return WorkingHours|null
     */
    public function getWorkingHours()
    {
        return $this->workingHours;
    }

    /**
     * Get latitude
     *
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Get longitude
     *
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Check if this is a locker
     *
     * @return bool
     */
    public function isLocker()
    {
        return $this->type === self::TYPE_LOCKER;
    }

    /**
     * Check if this is a standard office
     *
     * @return bool
     */
    public function isOffice()
    {
        return $this->type === self::TYPE_OFFICE;
    }

    /**
     * Check if this is a warehouse
     *
     * @return bool
     */
    public function isWarehouse()
    {
        return $this->type === self::TYPE_WAREHOUSE;
    }

    /**
     * Check if office has coordinates
     *
     * @return bool
     */
    public function hasCoordinates()
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get full address including city
     *
     * @return string
     */
    public function getFullAddress()
    {
        $parts = array($this->address);

        if ($this->city !== null) {
            $parts[] = $this->city->getFullName();
        }

        return implode(', ', $parts);
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $office = new self();
        $office->id = isset($data['id']) ? (int) $data['id'] : 0;
        $office->name = isset($data['name']) ? $data['name'] : '';
        $office->code = isset($data['code']) ? $data['code'] : '';
        $office->type = isset($data['type']) ? $data['type'] : self::TYPE_OFFICE;
        $office->address = isset($data['address']) ? $data['address'] : '';
        $office->phone = isset($data['phone']) ? $data['phone'] : null;
        $office->email = isset($data['email']) ? $data['email'] : null;
        $office->latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $office->longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;

        if (isset($data['city']) && is_array($data['city'])) {
            $office->city = City::fromArray($data['city']);
        }

        // Support direct city_id field (fallback when city object is not provided)
        if (isset($data['city_id'])) {
            $office->cityId = (int) $data['city_id'];
        }

        if (isset($data['working_hours']) && is_array($data['working_hours'])) {
            $office->workingHours = WorkingHours::fromArray($data['working_hours']);
        }

        return $office;
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
            'code' => $this->code,
            'type' => $this->type,
            'city' => $this->city !== null ? $this->city->toArray() : null,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'working_hours' => $this->workingHours !== null ? $this->workingHours->toArray() : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        );
    }
}
