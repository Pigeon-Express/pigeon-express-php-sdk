<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Shipment Status DTO
 *
 * Represents a shipment status.
 */
class ShipmentStatus implements Arrayable
{
    /**
     * Status codes
     */
    const REGISTERED = 'shipment_registered';
    const ACCEPTED_IN_OFFICE = 'shipment_accepted_in_office';
    const COURIER_ASSIGNED = 'shipment_courier_assigned';
    const IN_SORTING_CENTER = 'shipment_in_sorting_center';
    const IN_DELIVERY = 'shipment_in_delivery';
    const DELIVERED_TO_OFFICE = 'shipment_delivered_to_office';
    const DELIVERED_TO_RECIPIENT = 'shipment_delivered_to_recipient';
    const CANCELLED = 'shipment_cancelled';
    const RETURNED = 'shipment_returned';
    const DELIVERY_PROBLEM = 'shipment_delivery_problem';

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $color;

    /**
     * Get status code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get status name (localized)
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get status color (hex)
     *
     * @return string|null
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Check if shipment is delivered
     *
     * @return bool
     */
    public function isDelivered()
    {
        return in_array($this->code, array(
            self::DELIVERED_TO_OFFICE,
            self::DELIVERED_TO_RECIPIENT,
        ));
    }

    /**
     * Check if shipment is in transit
     *
     * @return bool
     */
    public function isInTransit()
    {
        return in_array($this->code, array(
            self::COURIER_ASSIGNED,
            self::IN_SORTING_CENTER,
            self::IN_DELIVERY,
        ));
    }

    /**
     * Check if shipment is pending pickup
     *
     * @return bool
     */
    public function isPending()
    {
        return in_array($this->code, array(
            self::REGISTERED,
            self::ACCEPTED_IN_OFFICE,
        ));
    }

    /**
     * Check if shipment has a problem
     *
     * @return bool
     */
    public function hasProblem()
    {
        return in_array($this->code, array(
            self::CANCELLED,
            self::RETURNED,
            self::DELIVERY_PROBLEM,
        ));
    }

    /**
     * Check if shipment is final (no more status changes expected)
     *
     * @return bool
     */
    public function isFinal()
    {
        return in_array($this->code, array(
            self::DELIVERED_TO_RECIPIENT,
            self::CANCELLED,
            self::RETURNED,
        ));
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $status = new self();
        $status->code = isset($data['code']) ? $data['code'] : '';
        $status->name = isset($data['name']) ? $data['name'] : '';
        $status->color = isset($data['color']) ? $data['color'] : null;

        return $status;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'code' => $this->code,
            'name' => $this->name,
            'color' => $this->color,
        );
    }
}
