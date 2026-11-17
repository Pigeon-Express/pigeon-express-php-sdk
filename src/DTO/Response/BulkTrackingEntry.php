<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Bulk Tracking Entry DTO
 *
 * One shipment's entry in the answer of POST /shipments/track/bulk.
 *
 * Every entry carries `found`. An unknown or foreign reference comes back with
 * `found` false and an `error` message rather than failing the whole request.
 *
 * The status is normalised on the entry, on each tracking event and on each
 * linked shipment to two strings, `status_code` and `status` (the name),
 * whether the carrier sent an object `{code, name}` or a name beside a code.
 * Different endpoints of the carrier do both.
 */
class BulkTrackingEntry implements Arrayable
{
    /**
     * @var string The reference the entry was keyed by
     */
    private $reference;

    /**
     * @var array The normalised entry
     */
    private $data = array();

    /**
     * Create from one entry of the response.
     *
     * @param array $data The entry
     * @param string|null $reference The key it was listed under, used when the
     *                               entry itself carries no reference_number
     * @return self
     */
    public static function fromArray(array $data, $reference = null)
    {
        $entry = new self();
        $entry->data = self::normalise($data);
        $entry->reference = isset($entry->data['reference_number']) && $entry->data['reference_number'] !== ''
            ? (string) $entry->data['reference_number']
            : (string) $reference;

        return $entry;
    }

    /**
     * Normalise the status on an entry, an event or a linked shipment, and
     * recursively on the lists it holds.
     *
     * @param array $entry
     * @return array
     */
    public static function normalise(array $entry)
    {
        if (isset($entry['status']) && is_array($entry['status'])) {
            $status = $entry['status'];
            $entry['status_code'] = isset($status['code'])
                ? (string) $status['code']
                : (isset($entry['status_code']) ? (string) $entry['status_code'] : '');
            $entry['status'] = isset($status['name']) ? (string) $status['name'] : '';
        }

        foreach (array('tracking', 'chain_before', 'chain_after') as $list) {
            if (!isset($entry[$list]) || !is_array($entry[$list])) {
                continue;
            }

            foreach ($entry[$list] as $index => $item) {
                if (is_array($item)) {
                    $entry[$list][$index] = self::normalise($item);
                }
            }
        }

        return $entry;
    }

    /**
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->reference;
    }

    /**
     * Whether the carrier knows this shipment and it belongs to the account.
     *
     * @return bool
     */
    public function isFound()
    {
        return !empty($this->data['found']);
    }

    /**
     * Why the shipment was not found, null when it was.
     *
     * @return string|null
     */
    public function getError()
    {
        return isset($this->data['error']) ? (string) $this->data['error'] : null;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return isset($this->data['status_code']) ? (string) $this->data['status_code'] : '';
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        return isset($this->data['status']) && is_scalar($this->data['status']) ? (string) $this->data['status'] : '';
    }

    /**
     * The status as a ShipmentStatus, for its isDelivered() and friends.
     *
     * @return ShipmentStatus
     */
    public function getStatus()
    {
        return ShipmentStatus::fromArray(array('code' => $this->getStatusCode(), 'name' => $this->getStatusName()));
    }

    /**
     * @return string|null
     */
    public function getExternalReference()
    {
        return isset($this->data['external_reference']) ? (string) $this->data['external_reference'] : null;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt()
    {
        return isset($this->data['created_at']) ? (string) $this->data['created_at'] : null;
    }

    /**
     * @return string|null
     */
    public function getExpectedDeliveryDate()
    {
        return isset($this->data['expected_delivery_date']) ? (string) $this->data['expected_delivery_date'] : null;
    }

    /**
     * @return string|null
     */
    public function getDeliveryDate()
    {
        return isset($this->data['delivery_date']) ? (string) $this->data['delivery_date'] : null;
    }

    /**
     * The status history, oldest first, each with `status_code`, `status` and
     * `created_at`.
     *
     * @return array[]
     */
    public function getTracking()
    {
        return $this->listField('tracking');
    }

    /**
     * The latest event in the history, or null.
     *
     * @return array|null
     */
    public function getLatestEvent()
    {
        $tracking = $this->getTracking();

        return !empty($tracking) ? $tracking[count($tracking) - 1] : null;
    }

    /**
     * Linked shipments created before this one (for example the original of
     * a return to sender).
     *
     * @return array[]
     */
    public function getChainBefore()
    {
        return $this->listField('chain_before');
    }

    /**
     * Linked shipments created after this one (for example its return to
     * sender).
     *
     * @return array[]
     */
    public function getChainAfter()
    {
        return $this->listField('chain_after');
    }

    /**
     * The normalised entry, every field the carrier sent included.
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
    private function listField($key)
    {
        return isset($this->data[$key]) && is_array($this->data[$key]) ? array_values($this->data[$key]) : array();
    }
}
