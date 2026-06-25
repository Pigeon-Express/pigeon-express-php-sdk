<?php

namespace PigeonExpress\Resources;

use PigeonExpress\Contracts\Arrayable;
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Response\Shipment;
use PigeonExpress\DTO\Response\ShipmentCalculation;
use PigeonExpress\DTO\Response\TrackingInfo;

/**
 * Shipments Resource
 *
 * API resource for managing shipments.
 */
class Shipments extends AbstractResource
{
    /**
     * Calculate shipment price
     *
     * @param array|CalculateShipmentRequest $data Calculation request data
     * @return ShipmentCalculation
     */
    public function calculate($data)
    {
        $payload = $this->preparePayload($data);
        $response = $this->getClient()->post('shipments/calculate', $payload);

        return ShipmentCalculation::fromArray($response->getData('data'));
    }

    /**
     * Create a new shipment
     *
     * @param array|CreateShipmentRequest $data Shipment data
     * @return Shipment
     */
    public function create($data)
    {
        // Validate if using DTO
        if ($data instanceof CreateShipmentRequest) {
            $data->validate();
        }

        $payload = $this->preparePayload($data);
        $response = $this->getClient()->post('shipments', $payload);

        return Shipment::fromArray($response->getData('data'));
    }

    /**
     * Track a shipment by reference number
     *
     * @param string $referenceNumber Reference number (e.g., "PGN-2026-00012345")
     * @return TrackingInfo
     */
    public function track($referenceNumber)
    {
        $response = $this->getClient()->get('shipments/' . $referenceNumber . '/track');

        return TrackingInfo::fromArray($response->getData('data'));
    }

    /**
     * Prepare payload from data
     *
     * @param mixed $data
     * @return array
     */
    private function preparePayload($data)
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if (is_array($data)) {
            return $this->normalizeArrayPayload($data);
        }

        return array();
    }

    /**
     * Normalize array payload (convert nested arrays if needed)
     *
     * @param array $data
     * @return array
     */
    private function normalizeArrayPayload(array $data)
    {
        // Normalize packages
        if (isset($data['packages']) && is_array($data['packages'])) {
            $packages = array();
            foreach ($data['packages'] as $package) {
                if ($package instanceof Arrayable) {
                    $packages[] = $package->toArray();
                } else {
                    $packages[] = $package;
                }
            }
            $data['packages'] = $packages;
        }

        // Normalize addresses
        foreach (array('sender_address', 'delivery_address') as $key) {
            if (isset($data[$key]) && $data[$key] instanceof Arrayable) {
                $data[$key] = $data[$key]->toArray();
            }
        }

        return $data;
    }
}
