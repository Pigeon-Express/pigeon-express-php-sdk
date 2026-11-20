<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * What the sender can change on a shipment now
 * (`GET /shipments/{referenceNumber}/actions`): per quick action whether it
 * is allowed and, when not, why; the current values to fill the forms from;
 * the services that can be added or removed; the pricing and, on a
 * redirected shipment, the active reference number.
 *
 * Asks the courier's own system, unlike `quick_actions` on the shipment
 * details, which is only a hint for which buttons to show.
 *
 * {@see toArray()} returns the answer with `actions` always holding all four
 * actions, each as {allowed: bool, reason_code: string, reason: string};
 * every other field is kept as sent.
 */
class ShipmentActions implements Arrayable
{
    /**
     * The four quick actions, as the API names them in `actions` and
     * `quick_actions`. In the path they take a hyphen (`change-delivery`);
     * both spellings are accepted wherever an action is named in this SDK.
     */
    const CHANGE_DELIVERY = 'change_delivery';
    const SERVICES = 'services';
    const COD = 'cod';
    const DECLARED_VALUE = 'declared_value';

    /**
     * @var array
     */
    private $data = array();

    /**
     * The API's name of each action => its path segment.
     *
     * @return array<string, string>
     */
    public static function actionSegments()
    {
        return array(
            self::CHANGE_DELIVERY => 'change-delivery',
            self::SERVICES => 'services',
            self::COD => 'cod',
            self::DECLARED_VALUE => 'declared-value',
        );
    }

    /**
     * The path segment of an action from either spelling, or an empty string
     * for anything that is not one of the four.
     *
     * @param string $action
     * @return string
     */
    public static function segmentOf($action)
    {
        $name = self::nameOf($action);
        $all = self::actionSegments();

        return $name !== '' ? $all[$name] : '';
    }

    /**
     * The API's name of an action (with an underscore) from either spelling,
     * or an empty string for anything that is not one of the four.
     *
     * @param string $action
     * @return string
     */
    public static function nameOf($action)
    {
        $action = is_scalar($action) ? strtolower(trim((string) $action)) : '';
        $all = self::actionSegments();

        if (isset($all[$action])) {
            return $action;
        }

        $name = array_search($action, $all, true);

        return $name !== false ? (string) $name : '';
    }

    /**
     * @param array $data The API's `data`
     * @return self
     */
    public static function fromArray(array $data)
    {
        $actions = isset($data['actions']) && is_array($data['actions']) ? $data['actions'] : array();
        $normalised = array();

        foreach (array_keys(self::actionSegments()) as $name) {
            $state = isset($actions[$name]) && is_array($actions[$name]) ? $actions[$name] : array();
            $normalised[$name] = array(
                'allowed' => self::flag(isset($state['allowed']) ? $state['allowed'] : false),
                'reason_code' => isset($state['reason_code']) && is_scalar($state['reason_code']) ? (string) $state['reason_code'] : '',
                'reason' => isset($state['reason']) && is_scalar($state['reason']) ? (string) $state['reason'] : '',
            );
        }

        $data['actions'] = $normalised;

        $result = new self();
        $result->data = $data;

        return $result;
    }

    /**
     * Whether an action is possible now. Takes either spelling
     * (`change_delivery` or `change-delivery`).
     *
     * @param string $action
     * @return bool
     */
    public function isAllowed($action)
    {
        $state = $this->state($action);

        return $state !== null && $state['allowed'];
    }

    /**
     * Why an action is not possible (e.g. `action_not_allowed`), or an empty
     * string.
     *
     * @param string $action
     * @return string
     */
    public function getReasonCode($action)
    {
        $state = $this->state($action);

        return $state !== null ? $state['reason_code'] : '';
    }

    /**
     * The reason an action is not possible, in Bulgarian and meant to be
     * shown, or an empty string.
     *
     * @param string $action
     * @return string
     */
    public function getReason($action)
    {
        $state = $this->state($action);

        return $state !== null ? $state['reason'] : '';
    }

    /**
     * All four actions keyed by the API's name, each
     * {allowed: bool, reason_code: string, reason: string}.
     *
     * @return array<string, array{allowed: bool, reason_code: string, reason: string}>
     */
    public function getActions()
    {
        return $this->data['actions'];
    }

    /**
     * The services the `services` action accepts in `add`: {code, name} rows.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function getAddableServices()
    {
        return $this->services('addable_services');
    }

    /**
     * The services the `services` action accepts in `remove`: {code, name} rows.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function getRemovableServices()
    {
        return $this->services('removable_services');
    }

    /**
     * The raw `pricing` block: total, paid_amount, payment_mode, currency.
     *
     * @return array
     */
    public function getPricing()
    {
        return isset($this->data['pricing']) && is_array($this->data['pricing']) ? $this->data['pricing'] : array();
    }

    /**
     * The raw `current` block: the shipment's values to fill the forms from
     * (delivery_type, delivery_office_id, delivery_address, services,
     * cod_amount, allow_cod_card_payment, cod_from_items, cod_items_amount,
     * declared_value).
     *
     * @return array
     */
    public function getCurrent()
    {
        return isset($this->data['current']) && is_array($this->data['current']) ? $this->data['current'] : array();
    }

    /**
     * On a redirected shipment the number of the active one, otherwise an
     * empty string.
     *
     * @return string
     */
    public function getActiveReferenceNumber()
    {
        $value = isset($this->data['active_reference_number']) ? $this->data['active_reference_number'] : '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $action
     * @return array{allowed: bool, reason_code: string, reason: string}|null
     */
    private function state($action)
    {
        $name = self::nameOf($action);

        return $name !== '' && isset($this->data['actions'][$name]) ? $this->data['actions'][$name] : null;
    }

    /**
     * @param string $key
     * @return array<int, array{code: string, name: string}>
     */
    private function services($key)
    {
        $rows = isset($this->data[$key]) && is_array($this->data[$key]) ? $this->data[$key] : array();
        $out = array();

        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['code']) || !is_scalar($row['code']) || trim((string) $row['code']) === '') {
                continue;
            }
            $out[] = array(
                'code' => trim((string) $row['code']),
                'name' => isset($row['name']) && is_scalar($row['name']) ? (string) $row['name'] : '',
            );
        }

        return $out;
    }

    /**
     * The API's booleans may come as true/false, "true"/"false" or 1/0.
     *
     * @param mixed $value
     * @return bool
     */
    private static function flag($value)
    {
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), array('1', 'true', 'yes'), true);
        }

        return (bool) $value;
    }
}
