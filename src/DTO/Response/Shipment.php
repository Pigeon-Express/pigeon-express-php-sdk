<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Shipment DTO
 *
 * Represents a created shipment response.
 */
class Shipment implements Arrayable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $referenceNumber;

    /**
     * @var ShipmentStatus
     */
    private $status;

    /**
     * @var float
     */
    private $deliveryPrice;

    /**
     * @var string
     */
    private $expectedDeliveryDate;

    /**
     * @var Package[]
     */
    private $packages = array();

    /**
     * @var string
     */
    private $createdAt;

    /**
     * Get shipment ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get reference number (e.g., "PGN-2026-00012345")
     *
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * Get status
     *
     * @return ShipmentStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get delivery price
     *
     * @return float
     */
    public function getDeliveryPrice()
    {
        return $this->deliveryPrice;
    }

    /**
     * Get expected delivery date
     *
     * @return string
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
     * Get packages
     *
     * @return Package[]
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * Get first package (most shipments have single package)
     *
     * @return Package|null
     */
    public function getFirstPackage()
    {
        return !empty($this->packages) ? $this->packages[0] : null;
    }

    /**
     * Get created at timestamp
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get created at as DateTime
     *
     * @return \DateTime|null
     */
    public function getCreatedAtDateTime()
    {
        if (empty($this->createdAt)) {
            return null;
        }

        try {
            return new \DateTime($this->createdAt);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get primary tracking number
     *
     * @return string|null
     */
    public function getTrackingNumber()
    {
        $package = $this->getFirstPackage();

        return $package !== null ? $package->getTrackingNumber() : null;
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $shipment = new self();
        $shipment->id = isset($data['id']) ? (int) $data['id'] : 0;
        $shipment->referenceNumber = isset($data['reference_number']) ? $data['reference_number'] : '';
        $shipment->deliveryPrice = isset($data['delivery_price']) ? (float) $data['delivery_price'] : 0.0;
        $shipment->expectedDeliveryDate = isset($data['expected_delivery_date']) ? $data['expected_delivery_date'] : '';
        $shipment->createdAt = isset($data['created_at']) ? $data['created_at'] : '';

        if (isset($data['status']) && is_array($data['status'])) {
            $shipment->status = ShipmentStatus::fromArray($data['status']);
        } else {
            $shipment->status = ShipmentStatus::fromArray(array('code' => '', 'name' => ''));
        }

        if (isset($data['packages']) && is_array($data['packages'])) {
            foreach ($data['packages'] as $package) {
                $shipment->packages[] = Package::fromArray($package);
            }
        }

        return $shipment;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $packages = array();
        foreach ($this->packages as $package) {
            $packages[] = $package->toArray();
        }

        return array(
            'id' => $this->id,
            'reference_number' => $this->referenceNumber,
            'status' => $this->status->toArray(),
            'delivery_price' => $this->deliveryPrice,
            'expected_delivery_date' => $this->expectedDeliveryDate,
            'packages' => $packages,
            'created_at' => $this->createdAt,
        );
    }
}
