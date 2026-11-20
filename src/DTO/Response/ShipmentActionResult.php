<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * The answer to a quick action
 * (`POST /shipments/{referenceNumber}/actions/{action}`): its price and,
 * for a real one, what it did.
 *
 * A dry run changes nothing and only carries the price. After a real change
 * of the delivery place made once the shipment is past newly registered,
 * {@see getNewReferenceNumber()} holds the new shipment (a redirect); it is
 * always empty on a dry run. The answer does not carry the shipment: read it
 * again after any real action.
 */
class ShipmentActionResult implements Arrayable
{
    /**
     * @var array The API's `data`
     */
    private $data = array();

    /**
     * @var string The API's `message`
     */
    private $message = '';

    /**
     * @param array $data The API's `data`
     * @param string $message The API's `message`
     * @return self
     */
    public static function fromArray(array $data, $message = '')
    {
        $result = new self();
        $result->data = $data;
        $result->message = is_scalar($message) ? (string) $message : '';

        return $result;
    }

    /**
     * Whether this was only a price (nothing changed).
     *
     * @return bool
     */
    public function isDryRun()
    {
        return !empty($this->data['dry_run']) && $this->data['dry_run'] !== 'false';
    }

    /**
     * The raw `price` block: previous_total, new_total, paid_amount,
     * amount_due, payment_mode and, when the courier's system gives them,
     * breakdown.
     *
     * @return array
     */
    public function getPrice()
    {
        return isset($this->data['price']) && is_array($this->data['price']) ? $this->data['price'] : array();
    }

    /**
     * @return float|null
     */
    public function getPreviousTotal()
    {
        return $this->priceField('previous_total');
    }

    /**
     * @return float|null
     */
    public function getNewTotal()
    {
        return $this->priceField('new_total');
    }

    /**
     * @return float|null
     */
    public function getAmountDue()
    {
        return $this->priceField('amount_due');
    }

    /**
     * paid, sender_unpaid, receiver or deferred; empty when not given.
     *
     * @return string
     */
    public function getPaymentMode()
    {
        $price = $this->getPrice();

        return isset($price['payment_mode']) && is_scalar($price['payment_mode']) ? (string) $price['payment_mode'] : '';
    }

    /**
     * The raw `result` block: reference_number, new_reference_number,
     * expected_delivery_date, removed_services.
     *
     * @return array
     */
    public function getResult()
    {
        return isset($this->data['result']) && is_array($this->data['result']) ? $this->data['result'] : array();
    }

    /**
     * The new shipment's number after a redirect, or an empty string (always
     * on a dry run and on a change made in place).
     *
     * @return string
     */
    public function getNewReferenceNumber()
    {
        $result = $this->getResult();
        $value = isset($result['new_reference_number']) ? $result['new_reference_number'] : '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Services removed together with the change, for example the card
     * payment when cash on delivery is removed. Also given on a dry run.
     *
     * @return string[]
     */
    public function getRemovedServices()
    {
        $result = $this->getResult();
        $rows = isset($result['removed_services']) && is_array($result['removed_services']) ? $result['removed_services'] : array();
        $out = array();

        foreach ($rows as $code) {
            if (is_scalar($code) && trim((string) $code) !== '') {
                $out[] = trim((string) $code);
            }
        }

        return $out;
    }

    /**
     * The API's message (Bulgarian), e.g. "Промяната е направена.".
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * The API's `data` as sent.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $key
     * @return float|null
     */
    private function priceField($key)
    {
        $price = $this->getPrice();

        return isset($price[$key]) && is_numeric($price[$key]) ? (float) $price[$key] : null;
    }
}
