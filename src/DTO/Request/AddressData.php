<?php

namespace PigeonExpress\DTO\Request;

use PigeonExpress\Contracts\Arrayable;

/**
 * Address Data DTO
 *
 * Represents a delivery or pickup address.
 */
class AddressData implements Arrayable
{
    /**
     * @var int City ID
     */
    private $cityId;

    /**
     * @var int|null Street ID
     */
    private $streetId;

    /**
     * @var string|null Street number
     */
    private $streetNumber;

    /**
     * @var string|null Building number
     */
    private $buildingNumber;

    /**
     * @var string|null Entrance
     */
    private $entrance;

    /**
     * @var string|null Floor
     */
    private $floor;

    /**
     * @var string|null Apartment
     */
    private $apartment;

    /**
     * @var string|null Postal code
     */
    private $postalCode;

    /**
     * @var string|null Neighborhood
     */
    private $neighborhood;

    /**
     * @var string|null Contact name
     */
    private $contactName;

    /**
     * @var string|null Contact phone
     */
    private $contactPhone;

    /**
     * @var string|null Additional info
     */
    private $additionalInfo;

    /**
     * Create address data
     *
     * @param int $cityId City ID
     */
    public function __construct($cityId)
    {
        $this->cityId = (int) $cityId;
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
     * Set street ID
     *
     * @param int $streetId
     * @return $this
     */
    public function setStreetId($streetId)
    {
        $this->streetId = (int) $streetId;
        return $this;
    }

    /**
     * Get street ID
     *
     * @return int|null
     */
    public function getStreetId()
    {
        return $this->streetId;
    }

    /**
     * Set street number
     *
     * @param string $streetNumber
     * @return $this
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
        return $this;
    }

    /**
     * Get street number
     *
     * @return string|null
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * Set building number
     *
     * @param string $buildingNumber
     * @return $this
     */
    public function setBuildingNumber($buildingNumber)
    {
        $this->buildingNumber = $buildingNumber;
        return $this;
    }

    /**
     * Get building number
     *
     * @return string|null
     */
    public function getBuildingNumber()
    {
        return $this->buildingNumber;
    }

    /**
     * Set entrance
     *
     * @param string $entrance
     * @return $this
     */
    public function setEntrance($entrance)
    {
        $this->entrance = $entrance;
        return $this;
    }

    /**
     * Get entrance
     *
     * @return string|null
     */
    public function getEntrance()
    {
        return $this->entrance;
    }

    /**
     * Set floor
     *
     * @param string $floor
     * @return $this
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;
        return $this;
    }

    /**
     * Get floor
     *
     * @return string|null
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * Set apartment
     *
     * @param string $apartment
     * @return $this
     */
    public function setApartment($apartment)
    {
        $this->apartment = $apartment;
        return $this;
    }

    /**
     * Get apartment
     *
     * @return string|null
     */
    public function getApartment()
    {
        return $this->apartment;
    }

    /**
     * Set postal code
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
     * Get postal code
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set neighborhood
     *
     * @param string $neighborhood
     * @return $this
     */
    public function setNeighborhood($neighborhood)
    {
        $this->neighborhood = $neighborhood;
        return $this;
    }

    /**
     * Get neighborhood
     *
     * @return string|null
     */
    public function getNeighborhood()
    {
        return $this->neighborhood;
    }

    /**
     * Set contact name
     *
     * @param string $contactName
     * @return $this
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
        return $this;
    }

    /**
     * Get contact name
     *
     * @return string|null
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * Set contact phone
     *
     * @param string $contactPhone
     * @return $this
     */
    public function setContactPhone($contactPhone)
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    /**
     * Get contact phone
     *
     * @return string|null
     */
    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    /**
     * Set additional info
     *
     * @param string $additionalInfo
     * @return $this
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    /**
     * Get additional info
     *
     * @return string|null
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'city_id' => $this->cityId,
        );

        $optionalFields = array(
            'street_id' => $this->streetId,
            'street_number' => $this->streetNumber,
            'building_number' => $this->buildingNumber,
            'entrance' => $this->entrance,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'postal_code' => $this->postalCode,
            'neighborhood' => $this->neighborhood,
            'contact_name' => $this->contactName,
            'contact_phone' => $this->contactPhone,
            'additional_info' => $this->additionalInfo,
        );

        foreach ($optionalFields as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
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
        $address = new self(isset($data['city_id']) ? $data['city_id'] : 0);

        $setters = array(
            'street_id' => 'setStreetId',
            'street_number' => 'setStreetNumber',
            'building_number' => 'setBuildingNumber',
            'entrance' => 'setEntrance',
            'floor' => 'setFloor',
            'apartment' => 'setApartment',
            'postal_code' => 'setPostalCode',
            'neighborhood' => 'setNeighborhood',
            'contact_name' => 'setContactName',
            'contact_phone' => 'setContactPhone',
            'additional_info' => 'setAdditionalInfo',
        );

        foreach ($setters as $key => $method) {
            if (isset($data[$key])) {
                $address->$method($data[$key]);
            }
        }

        return $address;
    }
}
