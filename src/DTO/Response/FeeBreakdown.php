<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Fee Breakdown DTO
 *
 * Represents a single fee item in the price breakdown, for example one row of
 * `pricing.fees` on GET /shipments/{referenceNumber}.
 */
class FeeBreakdown implements Arrayable
{
    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var int|null
     */
    private $quantity;

    /**
     * Get the fee's service code, the same value passed in `service_codes` on
     * creation (for example `cod_fee`, `fuel_fee`).
     *
     * Null for a row without a linked service, such as a fee carried over from
     * an original shipment, and for breakdowns that carry no code at all.
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get fee name. Display text only, it may be renamed by the carrier.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get fee amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Get the quantity the fee was charged for, null when the API did not say.
     *
     * @return int|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $fee = new self();
        $fee->code = isset($data['code']) && $data['code'] !== '' ? (string) $data['code'] : null;
        $fee->name = isset($data['name']) ? $data['name'] : '';
        $fee->amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $fee->quantity = isset($data['quantity']) ? (int) $data['quantity'] : null;

        return $fee;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'code' => $this->code,
            'name' => $this->name,
            'amount' => $this->amount,
            'quantity' => $this->quantity,
        );
    }
}
