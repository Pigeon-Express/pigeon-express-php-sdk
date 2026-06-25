<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Fee Breakdown DTO
 *
 * Represents a single fee item in the price breakdown.
 */
class FeeBreakdown implements Arrayable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $amount;

    /**
     * Get fee name
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
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $fee = new self();
        $fee->name = isset($data['name']) ? $data['name'] : '';
        $fee->amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;

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
            'name' => $this->name,
            'amount' => $this->amount,
        );
    }
}
