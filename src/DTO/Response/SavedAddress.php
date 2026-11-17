<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;
use PigeonExpress\DTO\Request\AddressData;

/**
 * Saved Address DTO
 *
 * One address from the account's address book (GET /addresses). Its id goes
 * into `sender_address_id`, `delivery_address_id` (reverse shipments),
 * `return_address_id` or `return_documents_address_id` on a create request.
 * The API cannot add or change these addresses.
 */
class SavedAddress implements Arrayable
{
    /**
     * @var array
     */
    private $data = array();

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $address = new self();
        $address->data = $data;

        return $address;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return isset($this->data['id']) ? (int) $this->data['id'] : 0;
    }

    /**
     * Whether this is the account's default address, the one the API fills in
     * when none is sent.
     *
     * @return bool
     */
    public function isDefault()
    {
        return !empty($this->data['is_default']);
    }

    /**
     * The address as one line of text, ready for a dropdown.
     *
     * @return string
     */
    public function getLabel()
    {
        return isset($this->data['label']) ? (string) $this->data['label'] : '';
    }

    /**
     * @return string|null
     */
    public function getContactName()
    {
        return isset($this->data['contact_name']) ? (string) $this->data['contact_name'] : null;
    }

    /**
     * @return string|null
     */
    public function getContactPhone()
    {
        return isset($this->data['contact_phone']) ? (string) $this->data['contact_phone'] : null;
    }

    /**
     * The raw address block: `city_id`, `city_name`, `street_id`,
     * `street_name`, `street_number`, `building_number`, `entrance`, `floor`,
     * `apartment`, `neighborhood`, `postal_code`, `additional_info`.
     *
     * @return array
     */
    public function getAddress()
    {
        return isset($this->data['address']) && is_array($this->data['address']) ? $this->data['address'] : array();
    }

    /**
     * @return int
     */
    public function getCityId()
    {
        $address = $this->getAddress();

        return isset($address['city_id']) ? (int) $address['city_id'] : 0;
    }

    /**
     * @return string|null
     */
    public function getCityName()
    {
        return $this->addressString('city_name');
    }

    /**
     * Null for an address without a street, described in additional_info.
     *
     * @return int|null
     */
    public function getStreetId()
    {
        $address = $this->getAddress();

        return isset($address['street_id']) && (int) $address['street_id'] > 0 ? (int) $address['street_id'] : null;
    }

    /**
     * @return string|null Includes the street type as a full word
     */
    public function getStreetName()
    {
        return $this->addressString('street_name');
    }

    /**
     * @return string|null
     */
    public function getStreetNumber()
    {
        return $this->addressString('street_number');
    }

    /**
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->addressString('postal_code');
    }

    /**
     * The address as an AddressData, for endpoints that do not take a saved
     * address id, such as POST /shipments/calculate.
     *
     * @return AddressData
     */
    public function toAddressData()
    {
        // Only the fields an address request takes. The display names the
        // API adds here (city_name, street_name) are not part of one.
        $fields = array_intersect_key($this->getAddress(), array_flip(array(
            'city_id', 'street_id', 'street_number', 'building_number', 'entrance', 'floor',
            'apartment', 'postal_code', 'neighborhood', 'additional_info',
        )));

        if ($this->getContactName() !== null) {
            $fields['contact_name'] = $this->getContactName();
        }
        if ($this->getContactPhone() !== null) {
            $fields['contact_phone'] = $this->getContactPhone();
        }

        return AddressData::fromArray($fields);
    }

    /**
     * The whole entry as the API sent it.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $key
     * @return string|null
     */
    private function addressString($key)
    {
        $address = $this->getAddress();

        return isset($address[$key]) && $address[$key] !== '' ? (string) $address[$key] : null;
    }
}
