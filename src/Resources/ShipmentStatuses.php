<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\ShipmentStatus;

/**
 * Shipment Statuses Resource
 *
 * The carrier's list of shipment status codes with their names.
 */
class ShipmentStatuses extends AbstractResource
{
    /**
     * Get every shipment status the API lists (GET /shipment-statuses).
     *
     * Only active statuses are returned; some internal statuses are not
     * listed, so a code seen in tracking may be missing here.
     *
     * @return ShipmentStatus[]
     */
    public function all()
    {
        $response = $this->getClient()->get('shipment-statuses');
        $data = $response->getData('data');

        return $this->mapToDto(is_array($data) ? $data : array(), ShipmentStatus::class);
    }

    /**
     * Get every status name keyed by its code.
     *
     * @return array<string,string>
     */
    public function namesByCode()
    {
        $result = array();

        foreach ($this->all() as $status) {
            $result[$status->getCode()] = $status->getName();
        }

        return $result;
    }
}
