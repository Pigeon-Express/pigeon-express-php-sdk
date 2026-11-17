<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\SavedAddress;

/**
 * Addresses Resource
 *
 * The addresses saved in the account (its address book on the carrier's
 * client site). Read only: the API cannot add or change them.
 */
class Addresses extends AbstractResource
{
    /**
     * Get the saved addresses (GET /addresses).
     *
     * Active ones only, the default first. The endpoint takes no parameters
     * and is not paginated.
     *
     * @return SavedAddress[]
     */
    public function all()
    {
        $response = $this->getClient()->get('addresses');
        $data = $response->getData('data');

        return $this->mapToDto(is_array($data) ? $data : array(), SavedAddress::class);
    }

    /**
     * Find one saved address by its id, or null when the account has none
     * with that id (any more).
     *
     * @param int $id
     * @return SavedAddress|null
     */
    public function find($id)
    {
        foreach ($this->all() as $address) {
            if ($address->getId() === (int) $id) {
                return $address;
            }
        }

        return null;
    }

    /**
     * The account's default address, or null when it has none.
     *
     * @return SavedAddress|null
     */
    public function getDefault()
    {
        foreach ($this->all() as $address) {
            if ($address->isDefault()) {
                return $address;
            }
        }

        return null;
    }
}
