<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Tracking Event DTO
 *
 * Represents a single event in shipment tracking history.
 */
class TrackingEvent implements Arrayable
{
    /**
     * @var string
     */
    private $statusCode;

    /**
     * @var string
     */
    private $statusName;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * @var string|null
     */
    private $location;

    /**
     * @var string|null
     */
    private $note;

    /**
     * Get status code
     *
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get status name (localized)
     *
     * @return string
     */
    public function getStatusName()
    {
        return $this->statusName;
    }

    /**
     * Get timestamp
     *
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get timestamp as DateTime object
     *
     * @return \DateTime|null
     */
    public function getDateTime()
    {
        if (empty($this->timestamp)) {
            return null;
        }

        try {
            return new \DateTime($this->timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get location
     *
     * @return string|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Get note
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Check if this event indicates delivery
     *
     * @return bool
     */
    public function isDeliveryEvent()
    {
        return in_array($this->statusCode, array(
            ShipmentStatus::DELIVERED_TO_OFFICE,
            ShipmentStatus::DELIVERED_TO_RECIPIENT,
        ));
    }

    /**
     * Check if this event indicates a problem
     *
     * @return bool
     */
    public function isProblemEvent()
    {
        return in_array($this->statusCode, array(
            ShipmentStatus::CANCELLED,
            ShipmentStatus::RETURNED,
            ShipmentStatus::DELIVERY_PROBLEM,
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
        $event = new self();
        $event->statusCode = isset($data['status_code']) ? $data['status_code'] : '';
        $event->statusName = isset($data['status_name']) ? $data['status_name'] : '';
        $event->timestamp = isset($data['timestamp']) ? $data['timestamp'] : '';
        $event->location = isset($data['location']) ? $data['location'] : null;
        $event->note = isset($data['note']) ? $data['note'] : null;

        return $event;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'status_code' => $this->statusCode,
            'status_name' => $this->statusName,
            'timestamp' => $this->timestamp,
            'location' => $this->location,
            'note' => $this->note,
        );
    }
}
