<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Courier Request DTO
 *
 * A request for a courier to collect from an address, as returned by the
 * /courier-requests endpoints.
 */
class CourierRequest implements Arrayable
{
    /** Status codes. */
    const STATUS_PENDING = 'courier_request_pending';
    const STATUS_ASSIGNED = 'courier_request_assigned';
    const STATUS_IN_PROGRESS = 'courier_request_in_progress';
    const STATUS_COMPLETED = 'courier_request_completed';
    const STATUS_CANCELLED = 'courier_request_cancelled';
    const STATUS_FAILED = 'courier_request_failed';
    const STATUS_NO_ZONE_COURIER = 'courier_request_no_zone_courier';

    /** Request types. */
    const TYPE_SINGLE = 'single';
    const TYPE_GROUPAGE = 'groupage';

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
        $request = new self();

        $status = isset($data['status']) ? $data['status'] : null;
        if (is_array($status)) {
            $data['status'] = array(
                'code' => isset($status['code']) ? (string) $status['code'] : '',
                'name' => isset($status['name']) ? (string) $status['name'] : '',
            );
        } elseif ($status !== null) {
            $data['status'] = array(
                'code' => isset($data['status_code']) ? (string) $data['status_code'] : '',
                'name' => is_scalar($status) ? (string) $status : '',
            );
        }

        $request->data = $data;

        return $request;
    }

    /**
     * The public request number, for example `CR-458106744669`.
     *
     * @return string
     */
    public function getRequestNumber()
    {
        return isset($this->data['request_number']) ? (string) $this->data['request_number'] : '';
    }

    /**
     * @return string|null One of the TYPE_* constants
     */
    public function getRequestType()
    {
        return isset($this->data['request_type']) ? (string) $this->data['request_type'] : null;
    }

    /**
     * @return string One of the STATUS_* constants, empty when unknown
     */
    public function getStatusCode()
    {
        return isset($this->data['status']['code']) ? (string) $this->data['status']['code'] : '';
    }

    /**
     * @return string The status name, in Bulgarian
     */
    public function getStatusName()
    {
        return isset($this->data['status']['name']) ? (string) $this->data['status']['name'] : '';
    }

    /**
     * Whether nothing more will happen to the request: completed, cancelled
     * or failed. The API refuses to cancel such a request with 422.
     *
     * @return bool
     */
    public function isFinal()
    {
        return in_array($this->getStatusCode(), array(
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
        ), true);
    }

    /**
     * @return bool
     */
    public function isCancelled()
    {
        return $this->getStatusCode() === self::STATUS_CANCELLED;
    }

    /**
     * The contact person: `name`, `phone`, `email`.
     *
     * @return array
     */
    public function getContact()
    {
        return $this->arrayField('contact');
    }

    /**
     * @return string|null
     */
    public function getCompanyName()
    {
        return isset($this->data['company_name']) ? (string) $this->data['company_name'] : null;
    }

    /**
     * The pickup address: `city`, `address`, `neighborhood`,
     * `building_number`, `additional_info`.
     *
     * @return array
     */
    public function getPickup()
    {
        return $this->arrayField('pickup');
    }

    /**
     * @return string|null Y-m-d
     */
    public function getRequestedPickupDate()
    {
        return isset($this->data['requested_pickup_date']) ? (string) $this->data['requested_pickup_date'] : null;
    }

    /**
     * @return string|null interval or specific_time
     */
    public function getRequestedPickupTimeType()
    {
        return isset($this->data['requested_pickup_time_type']) ? (string) $this->data['requested_pickup_time_type'] : null;
    }

    /**
     * @return string|null
     */
    public function getRequestedPickupTimeFrom()
    {
        return isset($this->data['requested_pickup_time_from']) ? (string) $this->data['requested_pickup_time_from'] : null;
    }

    /**
     * @return string|null Null for a specific time
     */
    public function getRequestedPickupTimeTo()
    {
        return isset($this->data['requested_pickup_time_to']) ? (string) $this->data['requested_pickup_time_to'] : null;
    }

    /**
     * The package block: `type`, `description`, `estimated_weight`,
     * `estimated_dimensions`.
     *
     * @return array
     */
    public function getPackage()
    {
        return $this->arrayField('package');
    }

    /**
     * @return string|null
     */
    public function getSpecialInstructions()
    {
        return isset($this->data['special_instructions']) ? (string) $this->data['special_instructions'] : null;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return isset($this->data['notes']) ? (string) $this->data['notes'] : null;
    }

    /**
     * The reference of the shipment created from this request, once one exists.
     *
     * @return string|null
     */
    public function getCreatedShipmentReference()
    {
        return isset($this->data['created_shipment_reference']) ? (string) $this->data['created_shipment_reference'] : null;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt()
    {
        return isset($this->data['created_at']) ? (string) $this->data['created_at'] : null;
    }

    /**
     * The whole answer, with `status` normalised to {code, name}.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $key
     * @return array
     */
    private function arrayField($key)
    {
        return isset($this->data[$key]) && is_array($this->data[$key]) ? $this->data[$key] : array();
    }
}
