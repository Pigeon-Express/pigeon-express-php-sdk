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
     * @var string|null Latin transliteration of the name
     */
    private $nameEn;

    /**
     * @var string|null
     */
    private $typeEn;

    /**
     * @var string|null
     */
    private $districtEn;

    /**
     * @var string|null
     */
    private $municipalityEn;

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
     * Get the Latin transliteration of the city name.
     *
     * Needed to resolve a city typed in Latin script against Cyrillic data.
     *
     * @return string|null
     */
    public function getNameEn()
    {
        return $this->nameEn;
    }

    /**
     * @return string|null
     */
    public function getTypeEn()
    {
        return $this->typeEn;
    }

    /**
     * @return string|null
     */
    public function getDistrictEn()
    {
        return $this->districtEn;
    }

    /**
     * @return string|null
     */
    public function getMunicipalityEn()
    {
        return $this->municipalityEn;
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
        $city->nameEn = isset($data['name_en']) ? $data['name_en'] : null;
        $city->typeEn = isset($data['type_en']) ? $data['type_en'] : null;
        $city->districtEn = isset($data['district_en']) ? $data['district_en'] : null;
        $city->municipalityEn = isset($data['municipality_en']) ? $data['municipality_en'] : null;

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
            'name_en' => $this->nameEn,
            'type_en' => $this->typeEn,
            'district_en' => $this->districtEn,
            'municipality_en' => $this->municipalityEn,
        );
    }
}
