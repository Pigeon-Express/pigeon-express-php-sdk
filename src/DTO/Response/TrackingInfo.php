<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Tracking Info DTO
 *
 * Represents full tracking information for a shipment.
 */
class TrackingInfo implements Arrayable
{
    /**
     * @var string
     */
    private $referenceNumber;

    /**
     * @var ShipmentStatus
     */
    private $status;

    /**
     * @var array|null
     */
    private $currentLocation;

    /**
     * @var string
     */
    private $receiverName;

    /**
     * @var string
     */
    private $receiverPhone;

    /**
     * @var string|null
     */
    private $expectedDeliveryDate;

    /**
     * @var TrackingEvent[]
     */
    private $history = array();

    /**
     * Get reference number
     *
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * Get current status
     *
     * @return ShipmentStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get current location
     *
     * @return array|null ['office_id', 'office_name', 'city']
     */
    public function getCurrentLocation()
    {
        return $this->currentLocation;
    }

    /**
     * Get current location office name
     *
     * @return string|null
     */
    public function getCurrentLocationName()
    {
        return isset($this->currentLocation['office_name']) ? $this->currentLocation['office_name'] : null;
    }

    /**
     * Get current location city
     *
     * @return string|null
     */
    public function getCurrentLocationCity()
    {
        return isset($this->currentLocation['city']) ? $this->currentLocation['city'] : null;
    }

    /**
     * Get receiver name
     *
     * @return string
     */
    public function getReceiverName()
    {
        return $this->receiverName;
    }

    /**
     * Get receiver phone
     *
     * @return string
     */
    public function getReceiverPhone()
    {
        return $this->receiverPhone;
    }

    /**
     * Get expected delivery date
     *
     * @return string|null
     */
    public function getExpectedDeliveryDate()
    {
        return $this->expectedDeliveryDate;
    }

    /**
     * Get expected delivery date as DateTime
     *
     * @return \DateTime|null
     */
    public function getExpectedDeliveryDateTime()
    {
        if (empty($this->expectedDeliveryDate)) {
            return null;
        }

        try {
            return new \DateTime($this->expectedDeliveryDate);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get tracking history
     *
     * @return TrackingEvent[]
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Get the most recent tracking event
     *
     * @return TrackingEvent|null
     */
    public function getLatestEvent()
    {
        return !empty($this->history) ? $this->history[count($this->history) - 1] : null;
    }

    /**
     * Get the first tracking event
     *
     * @return TrackingEvent|null
     */
    public function getFirstEvent()
    {
        return !empty($this->history) ? $this->history[0] : null;
    }

    /**
     * Check if shipment is delivered
     *
     * @return bool
     */
    public function isDelivered()
    {
        return $this->status->isDelivered();
    }

    /**
     * Check if shipment is in transit
     *
     * @return bool
     */
    public function isInTransit()
    {
        return $this->status->isInTransit();
    }

    /**
     * Check if shipment has a problem
     *
     * @return bool
     */
    public function hasProblem()
    {
        return $this->status->hasProblem();
    }

    /**
     * Get number of events in history
     *
     * @return int
     */
    public function getEventCount()
    {
        return count($this->history);
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $tracking = new self();
        $tracking->referenceNumber = isset($data['reference_number']) ? $data['reference_number'] : '';
        $tracking->receiverName = isset($data['receiver_name']) ? $data['receiver_name'] : '';
        $tracking->receiverPhone = isset($data['receiver_phone']) ? $data['receiver_phone'] : '';
        $tracking->expectedDeliveryDate = isset($data['expected_delivery_date']) ? $data['expected_delivery_date'] : null;
        $tracking->currentLocation = isset($data['current_location']) ? $data['current_location'] : null;

        if (isset($data['status']) && is_array($data['status'])) {
            $tracking->status = ShipmentStatus::fromArray($data['status']);
        } else {
            $tracking->status = ShipmentStatus::fromArray(array('code' => '', 'name' => ''));
        }

        if (isset($data['history']) && is_array($data['history'])) {
            foreach ($data['history'] as $event) {
                $tracking->history[] = TrackingEvent::fromArray($event);
            }
        }

        return $tracking;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $history = array();
        foreach ($this->history as $event) {
            $history[] = $event->toArray();
        }

        return array(
            'reference_number' => $this->referenceNumber,
            'status' => $this->status->toArray(),
            'current_location' => $this->currentLocation,
            'receiver_name' => $this->receiverName,
            'receiver_phone' => $this->receiverPhone,
            'expected_delivery_date' => $this->expectedDeliveryDate,
            'history' => $history,
        );
    }
}
