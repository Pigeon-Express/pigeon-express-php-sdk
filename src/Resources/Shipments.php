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
    /** Standard thermal label, 100x90mm. */
    const LABEL_FORMAT_DEFAULT = 'default';

    /** PDF label, 100x150mm. */
    const LABEL_FORMAT_PDF150 = 'pdf150';

    /** Raw ZPL for Zebra printers at 300 DPI. Not a PDF, never preview it. */
    const LABEL_FORMAT_ZPL300 = 'zpl300';

    /** The standard label placed on an A4 sheet for an office printer. */
    const LABEL_FORMAT_A4 = 'a4';

    /**
     * Every label format the API accepts.
     *
     * @return string[]
     */
    public static function labelFormats()
    {
        return array(
            self::LABEL_FORMAT_DEFAULT,
            self::LABEL_FORMAT_PDF150,
            self::LABEL_FORMAT_ZPL300,
            self::LABEL_FORMAT_A4,
        );
    }

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

    /**
     * Cancel a shipment.
     *
     * Only shipments still in `shipment_registered` can be cancelled. Once the
     * courier has collected the parcel the API refuses, which is correct: at
     * that point the parcel physically exists in their network.
     *
     * @param string $referenceNumber
     * @return array Raw response data
     */
    public function cancel($referenceNumber)
    {
        $response = $this->getClient()->post('shipments/' . rawurlencode($referenceNumber) . '/cancel');

        $data = $response->getData('data');

        return is_array($data) ? $data : array();
    }

    /**
     * Fetch a shipping label.
     *
     * Returns the raw body rather than a DTO, because the payload is a PDF or,
     * for `zpl300`, a plain text ZPL program. The caller is expected to stream
     * it, so the content type is returned alongside it.
     *
     * @param string $referenceNumber
     * @param string|null $format One of default, pdf150, zpl300, a4
     * @param bool $download Ask for an attachment rather than inline display
     * @return array{body:string, content_type:string, filename:string}
     */
    public function label($referenceNumber, $format = null, $download = false)
    {
        $query = array();
        if ($format !== null && $format !== '' && $format !== self::LABEL_FORMAT_DEFAULT) {
            $query['format'] = $format;
        }
        if ($download) {
            $query['download'] = 'true';
        }

        $response = $this->getClient()->get(
            'shipments/' . rawurlencode($referenceNumber) . '/label',
            $query
        );

        $isZpl = $format === self::LABEL_FORMAT_ZPL300;

        // Each format gets its own name. Downloading two of them for the same
        // shipment otherwise produces one file and a copy of it.
        $suffixes = array(
            self::LABEL_FORMAT_PDF150 => '-100x150.pdf',
            self::LABEL_FORMAT_A4     => '-a4.pdf',
            self::LABEL_FORMAT_ZPL300 => '.zpl',
        );

        return array(
            'body' => $response->getBody(),
            'content_type' => $isZpl ? 'text/plain' : 'application/pdf',
            'filename' => $referenceNumber . (isset($suffixes[$format]) ? $suffixes[$format] : '.pdf'),
        );
    }
}
