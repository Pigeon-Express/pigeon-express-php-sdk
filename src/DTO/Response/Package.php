<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Package DTO
 *
 * Represents a package in a shipment response.
 */
class Package implements Arrayable
{
    /**
     * @var string
     */
    private $trackingNumber;

    /**
     * @var float
     */
    private $weight;

    /**
     * @var string
     */
    private $dimensions;

    /**
     * Get tracking number
     *
     * @return string
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Get dimensions (e.g., "30x20x15")
     *
     * @return string
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Parse dimensions into array
     *
     * @return array|null ['length' => x, 'width' => y, 'height' => z]
     */
    public function getParsedDimensions()
    {
        if (empty($this->dimensions)) {
            return null;
        }

        $parts = explode('x', $this->dimensions);

        if (count($parts) !== 3) {
            return null;
        }

        return array(
            'length' => (float) $parts[0],
            'width' => (float) $parts[1],
            'height' => (float) $parts[2],
        );
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $package = new self();
        $package->trackingNumber = isset($data['tracking_number']) ? $data['tracking_number'] : '';
        $package->weight = isset($data['weight']) ? (float) $data['weight'] : 0.0;
        $package->dimensions = isset($data['dimensions']) ? $data['dimensions'] : '';

        return $package;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'tracking_number' => $this->trackingNumber,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
        );
    }
}
