<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Shipment Calculation DTO
 *
 * Represents the result of a shipment price calculation.
 */
class ShipmentCalculation implements Arrayable
{
    /**
     * @var float
     */
    private $deliveryFee;

    /**
     * @var FeeBreakdown[]
     */
    private $feeBreakdown = array();

    /**
     * @var float|null
     */
    private $codFee;

    /**
     * @var float
     */
    private $total;

    /**
     * @var int
     */
    private $expectedDeliveryDays;

    /**
     * Get delivery fee
     *
     * @return float
     */
    public function getDeliveryFee()
    {
        return $this->deliveryFee;
    }

    /**
     * Get fee breakdown
     *
     * @return FeeBreakdown[]
     */
    public function getFeeBreakdown()
    {
        return $this->feeBreakdown;
    }

    /**
     * Get COD fee
     *
     * @return float|null
     */
    public function getCodFee()
    {
        return $this->codFee;
    }

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Get expected delivery days
     *
     * @return int
     */
    public function getExpectedDeliveryDays()
    {
        return $this->expectedDeliveryDays;
    }

    /**
     * Check if has COD fee
     *
     * @return bool
     */
    public function hasCodFee()
    {
        return $this->codFee !== null && $this->codFee > 0;
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $calculation = new self();

        // Support both old format (delivery_fee/total) and new format (shipping_price/total_price)
        $calculation->deliveryFee = isset($data['shipping_price'])
            ? (float) $data['shipping_price']
            : (isset($data['delivery_fee']) ? (float) $data['delivery_fee'] : 0.0);

        $calculation->codFee = isset($data['cod_fee']) ? (float) $data['cod_fee'] : null;

        $calculation->total = isset($data['total_price'])
            ? (float) $data['total_price']
            : (isset($data['total']) ? (float) $data['total'] : 0.0);

        $calculation->expectedDeliveryDays = isset($data['expected_delivery_days']) ? (int) $data['expected_delivery_days'] : 0;

        // Support both fee_breakdown and service_fees formats
        if (isset($data['fee_breakdown']) && is_array($data['fee_breakdown'])) {
            foreach ($data['fee_breakdown'] as $fee) {
                $calculation->feeBreakdown[] = FeeBreakdown::fromArray($fee);
            }
        } elseif (isset($data['service_fees']) && is_array($data['service_fees'])) {
            // Convert service_fees to fee_breakdown format
            foreach ($data['service_fees'] as $fee) {
                $calculation->feeBreakdown[] = FeeBreakdown::fromArray(array(
                    'name' => isset($fee['service_name']) ? $fee['service_name'] : '',
                    'amount' => isset($fee['fee']) ? $fee['fee'] : 0,
                ));
            }
        }

        return $calculation;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $breakdown = array();
        foreach ($this->feeBreakdown as $fee) {
            $breakdown[] = $fee->toArray();
        }

        return array(
            'delivery_fee' => $this->deliveryFee,
            'fee_breakdown' => $breakdown,
            'cod_fee' => $this->codFee,
            'total' => $this->total,
            'expected_delivery_days' => $this->expectedDeliveryDays,
        );
    }
}
