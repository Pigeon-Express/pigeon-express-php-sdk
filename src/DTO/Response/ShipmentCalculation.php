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
     * @var float Sum of all additional service fees applied by the API
     */
    private $totalServiceFees = 0.0;

    /**
     * @var float
     */
    private $total;

    /**
     * @var int
     */
    private $expectedDeliveryDays;

    /**
     * @var string|null
     */
    private $serviceType;

    /**
     * @var string|null
     */
    private $currency;

    /**
     * @var string[] Codes of the additional services the API actually applied
     */
    private $appliedServiceCodes = array();

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
     * Get the total of all additional service fees applied by the API.
     *
     * This is the authoritative figure for what the additional services cost.
     * It is returned as `total_service_fees` by POST /shipments/calculate.
     *
     * @return float
     */
    public function getTotalServiceFees()
    {
        return $this->totalServiceFees;
    }

    /**
     * Get service type (standard, express)
     *
     * @return string|null
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * Get currency code
     *
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Get the codes of the additional services the API actually applied.
     *
     * @return string[]
     */
    public function getAppliedServiceCodes()
    {
        return $this->appliedServiceCodes;
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

        $calculation->totalServiceFees = isset($data['total_service_fees'])
            ? (float) $data['total_service_fees']
            : 0.0;

        $calculation->total = isset($data['total_price'])
            ? (float) $data['total_price']
            : (isset($data['total']) ? (float) $data['total'] : 0.0);

        $calculation->expectedDeliveryDays = isset($data['expected_delivery_days']) ? (int) $data['expected_delivery_days'] : 0;

        $calculation->serviceType = isset($data['service_type']) ? (string) $data['service_type'] : null;
        $calculation->currency = isset($data['currency']) ? (string) $data['currency'] : null;

        // The API echoes back the service codes it actually applied, as a flat list.
        if (isset($data['service_codes']) && is_array($data['service_codes'])) {
            foreach ($data['service_codes'] as $code) {
                if (is_string($code) || is_numeric($code)) {
                    $calculation->appliedServiceCodes[] = (string) $code;
                }
            }
        }

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
            'shipping_price' => $this->deliveryFee,
            'fee_breakdown' => $breakdown,
            'cod_fee' => $this->codFee,
            'total_service_fees' => $this->totalServiceFees,
            'total' => $this->total,
            'total_price' => $this->total,
            'expected_delivery_days' => $this->expectedDeliveryDays,
            'service_type' => $this->serviceType,
            'currency' => $this->currency,
            'service_codes' => $this->appliedServiceCodes,
        );
    }
}
