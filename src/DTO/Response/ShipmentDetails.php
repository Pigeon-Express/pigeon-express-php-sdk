<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Shipment Details DTO
 *
 * Everything the carrier holds about one shipment, as returned by
 * GET /shipments/{referenceNumber} and by PUT /shipments/{referenceNumber}:
 * what it charged (`pricing`), the cash on delivery (`cod`), the linked
 * shipments (`related_shipments`) and the status.
 *
 * The typed getters cover the documented fields. {@see toArray()} returns the
 * whole answer, including fields this class has no getter for.
 */
class ShipmentDetails implements Arrayable
{
    /** Values of `pricing.payer`. */
    const PAYER_SENDER = 'sender';
    const PAYER_RECEIVER = 'receiver';
    const PAYER_OTHER = 'other';

    /**
     * @var array The full answer, with `status` normalised to {code, name}
     */
    private $data = array();

    /**
     * @var ShipmentStatus
     */
    private $status;

    /**
     * @var FeeBreakdown[]
     */
    private $fees = array();

    /**
     * Create from API response data
     *
     * The status is an object `{code, name}` on this endpoint and a plain name
     * beside `status_code` on others. Both are read, so getStatus()->getCode()
     * works whichever the carrier sent.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $details = new self();

        $status = isset($data['status']) ? $data['status'] : null;
        if (is_array($status)) {
            $data['status'] = array(
                'code' => isset($status['code']) ? (string) $status['code'] : '',
                'name' => isset($status['name']) ? (string) $status['name'] : '',
            );
        } else {
            $data['status'] = array(
                'code' => isset($data['status_code']) ? (string) $data['status_code'] : '',
                'name' => is_scalar($status) ? (string) $status : '',
            );
        }

        $details->data = $data;
        $details->status = ShipmentStatus::fromArray($data['status']);

        $pricing = $details->getPricing();
        if (isset($pricing['fees']) && is_array($pricing['fees'])) {
            foreach ($pricing['fees'] as $fee) {
                if (is_array($fee)) {
                    $details->fees[] = FeeBreakdown::fromArray($fee);
                }
            }
        }

        return $details;
    }

    /**
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->string('reference_number');
    }

    /**
     * The caller's own reference sent on creation, or null.
     *
     * @return string|null
     */
    public function getExternalReference()
    {
        return isset($this->data['external_reference']) ? (string) $this->data['external_reference'] : null;
    }

    /**
     * @return ShipmentStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string|null standard or express
     */
    public function getServiceType()
    {
        return isset($this->data['service_type']) ? (string) $this->data['service_type'] : null;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt()
    {
        return isset($this->data['created_at']) ? (string) $this->data['created_at'] : null;
    }

    /**
     * @return string|null Null until delivered
     */
    public function getDeliveryDate()
    {
        return isset($this->data['delivery_date']) ? (string) $this->data['delivery_date'] : null;
    }

    /**
     * The pickup end: `type`, and `office` or `address`.
     *
     * @return array
     */
    public function getPickup()
    {
        return $this->arrayField('pickup');
    }

    /**
     * The delivery end: `type`, and `office` or `address`.
     *
     * @return array
     */
    public function getDelivery()
    {
        return $this->arrayField('delivery');
    }

    /**
     * The delivery contact: `name` and `phone`, either may be null.
     *
     * @return array
     */
    public function getReceiver()
    {
        return $this->arrayField('receiver');
    }

    /**
     * @return float|null
     */
    public function getTotalWeight()
    {
        return isset($this->data['total_weight']) ? (float) $this->data['total_weight'] : null;
    }

    /**
     * The packages as the API lists them (`tracking_number`, `weight`,
     * `length`, `width`, `height`).
     *
     * @return array[]
     */
    public function getPackages()
    {
        return $this->arrayField('packages');
    }

    /**
     * @return array[]
     */
    public function getInventoryItems()
    {
        return $this->arrayField('inventory_items');
    }

    /**
     * The raw `pricing` block.
     *
     * @return array
     */
    public function getPricing()
    {
        return $this->arrayField('pricing');
    }

    /**
     * Who pays: one of the PAYER_* constants, or null.
     *
     * @return string|null
     */
    public function getPayer()
    {
        $pricing = $this->getPricing();

        return isset($pricing['payer']) ? (string) $pricing['payer'] : null;
    }

    /**
     * The base courier service fee. 0 on a shipment derived from another one
     * (a return or a redirect), whose base delivery is charged to the parent.
     *
     * @return float
     */
    public function getCourierService()
    {
        $pricing = $this->getPricing();

        return isset($pricing['courier_service']) ? (float) $pricing['courier_service'] : 0.0;
    }

    /**
     * Every charge outside the base delivery fee: additional services,
     * surcharges such as fuel, the COD fee, return and redirect fees.
     *
     * @return FeeBreakdown[]
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * The fee row with this service code, or null when none was charged.
     *
     * @param string $code For example `cod_fee`
     * @return FeeBreakdown|null
     */
    public function getFee($code)
    {
        foreach ($this->fees as $fee) {
            if ($fee->getCode() === (string) $code) {
                return $fee;
            }
        }

        return null;
    }

    /**
     * Whether a fee with this service code was charged.
     *
     * @param string $code
     * @return bool
     */
    public function hasFee($code)
    {
        return $this->getFee($code) !== null;
    }

    /**
     * The sum of every fee row.
     *
     * @return float
     */
    public function getFeesTotal()
    {
        $total = 0.0;
        foreach ($this->fees as $fee) {
            $total += $fee->getAmount();
        }

        return round($total, 2);
    }

    /**
     * The amount owed, `pricing.total`.
     *
     * @return float
     */
    public function getTotal()
    {
        $pricing = $this->getPricing();

        return isset($pricing['total']) ? (float) $pricing['total'] : 0.0;
    }

    /**
     * @return string|null
     */
    public function getPaymentStatus()
    {
        $pricing = $this->getPricing();

        return isset($pricing['payment_status']) ? (string) $pricing['payment_status'] : null;
    }

    /**
     * The raw `cod` block: `amount`, `original_cod_amount`, `currency`,
     * `payment_status`.
     *
     * @return array
     */
    public function getCod()
    {
        return $this->arrayField('cod');
    }

    /**
     * The cash on delivery amount, null when there is none.
     *
     * @return float|null
     */
    public function getCodAmount()
    {
        $cod = $this->getCod();

        return isset($cod['amount']) ? (float) $cod['amount'] : null;
    }

    /**
     * The routing block printed on the label: `code` and `label`.
     *
     * @return array
     */
    public function getRouting()
    {
        return $this->arrayField('routing');
    }

    /**
     * The direct neighbours of this shipment, each with `reference_number`
     * and `relation` (original, return_to_sender, return_document, redirected,
     * stored, partial_return).
     *
     * @return array[]
     */
    public function getRelatedShipments()
    {
        return $this->arrayField('related_shipments');
    }

    /**
     * The reference of the shipment this one was derived from, or null.
     *
     * @return string|null
     */
    public function getOriginalReference()
    {
        foreach ($this->getRelatedShipments() as $related) {
            if (is_array($related) && isset($related['relation']) && $related['relation'] === 'original') {
                return isset($related['reference_number']) ? (string) $related['reference_number'] : null;
            }
        }

        return null;
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
     * @return string
     */
    private function string($key)
    {
        return isset($this->data[$key]) ? (string) $this->data[$key] : '';
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
