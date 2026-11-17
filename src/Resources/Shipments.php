<?php

namespace PigeonExpress\Resources;

use PigeonExpress\Contracts\Arrayable;
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Response\BulkTrackingEntry;
use PigeonExpress\DTO\Response\Shipment;
use PigeonExpress\DTO\Response\ShipmentDetails;
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

    /** Most references the API tracks in one bulk request. */
    const TRACK_BULK_MAX = 100;

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
     * A 409 is not a refusal. It says an identical request is still being
     * processed, in practice often this caller's own earlier attempt whose
     * answer was lost to a timeout. It is repeated at once with the same body
     * (see {@see AbstractResource::sendWrite()}). Still 409 after the repeats,
     * a ConflictException is thrown and the outcome must be treated as
     * unknown, because the request in flight may yet create the shipment.
     *
     * A success that is the API's replay of an earlier answer is reported by
     * {@see getLastReplay()}. The shipment in it may have been cancelled since:
     * read it back with {@see get()} before keeping it.
     *
     * @param array|CreateShipmentRequest $data Shipment data
     * @return Shipment
     * @throws \PigeonExpress\Exceptions\ConflictException
     */
    public function create($data)
    {
        // Validate if using DTO
        if ($data instanceof CreateShipmentRequest) {
            $data->validate();
        }

        $payload = $this->preparePayload($data);
        $response = $this->sendWrite('POST', 'shipments', $payload);

        $shipment = $response->getData('data');

        return Shipment::fromArray(is_array($shipment) ? $shipment : array());
    }

    /**
     * Get everything the carrier holds about one shipment
     * (GET /shipments/{referenceNumber}): status, what it charged
     * (`pricing.fees`), the cash on delivery and the linked shipments.
     *
     * Accepts a shipment reference number or a package barcode from the label.
     * A shipment the account neither sent nor receives answers 404.
     *
     * @param string $referenceNumber
     * @return ShipmentDetails
     * @throws \PigeonExpress\Exceptions\NotFoundException
     */
    public function get($referenceNumber)
    {
        $response = $this->getClient()->get('shipments/' . rawurlencode((string) $referenceNumber));
        $data = $response->getData('data');

        return ShipmentDetails::fromArray(is_array($data) ? $data : array());
    }

    /**
     * Change a shipment that is still newly registered
     * (PUT /shipments/{referenceNumber}).
     *
     * The body is the whole shipment as it should become: whatever is left
     * out (services, cash on delivery, declared value, note, return office) is
     * cleared, not kept. Pass the body the create call would send; the pickup
     * end and a few create-only fields are ignored by the API.
     *
     * Answers 422 (ValidationException) when the shipment is no longer newly
     * registered or the body fails the create validation. A 409 is repeated
     * as on create, and a replay is reported by {@see getLastReplay()}: a
     * replayed answer describes the shipment as the earlier identical change
     * left it, not necessarily as it is now. No label is returned.
     *
     * @param string $referenceNumber
     * @param array|CreateShipmentRequest $data
     * @return ShipmentDetails The updated shipment
     * @throws \PigeonExpress\Exceptions\ConflictException
     * @throws \PigeonExpress\Exceptions\ValidationException
     */
    public function update($referenceNumber, $data)
    {
        $payload = $this->preparePayload($data);
        $response = $this->sendWrite('PUT', 'shipments/' . rawurlencode((string) $referenceNumber), $payload);

        $shipment = $response->getData('data');

        return ShipmentDetails::fromArray(is_array($shipment) ? $shipment : array());
    }

    /**
     * Track a shipment by reference number
     *
     * @param string $referenceNumber Reference number (e.g., "PGN-2026-00012345")
     * @return TrackingInfo
     */
    public function track($referenceNumber)
    {
        $response = $this->getClient()->get('shipments/' . rawurlencode((string) $referenceNumber) . '/track');

        return TrackingInfo::fromArray($response->getData('data'));
    }

    /**
     * Track many shipments (POST /shipments/track/bulk).
     *
     * References are trimmed, blanks dropped and duplicates removed. The API
     * takes at most {@see TRACK_BULK_MAX} per request, so a longer list is sent
     * in batches of that size, one request each, and the answers are merged.
     * A failing batch throws; callers that want to keep the progress of the
     * earlier batches pass at most TRACK_BULK_MAX references per call.
     *
     * Returns one entry per reference the API answered for, keyed by the
     * reference as a string. An unknown or foreign reference comes back with
     * isFound() false rather than failing the request.
     *
     * This is a read, although it is sent as POST.
     *
     * @param string[] $references
     * @return array<string,BulkTrackingEntry>
     */
    public function trackBulk(array $references)
    {
        $clean = array();
        foreach ($references as $reference) {
            if (is_scalar($reference) && trim((string) $reference) !== '') {
                $clean[] = trim((string) $reference);
            }
        }
        $clean = array_values(array_unique($clean));

        $entries = array();

        foreach (array_chunk($clean, static::TRACK_BULK_MAX) as $batch) {
            $response = $this->getClient()->post('shipments/track/bulk', array('references' => $batch));
            $data = $response->getData('data');

            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $key => $entry) {
                if (is_array($entry)) {
                    $tracked = BulkTrackingEntry::fromArray($entry, (string) $key);
                    $entries[(string) $key] = $tracked;
                }
            }
        }

        return $entries;
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
        // Not repeated on 409, matching the WooCommerce integration, but a
        // replayed answer is still reported by getLastReplay().
        $response = $this->sendWrite(
            'POST',
            'shipments/' . rawurlencode((string) $referenceNumber) . '/cancel',
            array(),
            false
        );

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
