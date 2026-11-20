<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * A parcel locker with its box sizes and the forecast of free boxes per size
 * by the end of today and of tomorrow (`GET /lockers/{code}/availability`).
 *
 * A forecast, not a reservation: parcels dropped off at the locker take boxes
 * nobody can foresee. A blocked locker has {@see isBlocked()} true and no free
 * boxes. Every field of the answer is kept and returned by {@see toArray()}.
 */
class LockerAvailability implements Arrayable
{
    const DAY_TODAY = 'today';
    const DAY_TOMORROW = 'tomorrow';

    /**
     * @var array The answer as the API sent it
     */
    private $data = array();

    /**
     * @var LockerBoxSize[]
     */
    private $boxSizes = array();

    /**
     * @param array $data The API's `data`
     * @return self
     */
    public static function fromArray(array $data)
    {
        $locker = new self();
        $locker->data = $data;

        $rows = isset($data['box_sizes']) && is_array($data['box_sizes']) ? $data['box_sizes'] : array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $box = LockerBoxSize::fromArray($row);
            if ($box->getSize() !== '') {
                $locker->boxSizes[] = $box;
            }
        }

        return $locker;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return isset($this->data['id']) ? (int) $this->data['id'] : 0;
    }

    /**
     * The locker's code, what it is looked up by.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->string('code');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->string('name');
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->string('address');
    }

    /**
     * @return float|null
     */
    public function getLatitude()
    {
        return isset($this->data['latitude']) && is_numeric($this->data['latitude']) ? (float) $this->data['latitude'] : null;
    }

    /**
     * @return float|null
     */
    public function getLongitude()
    {
        return isset($this->data['longitude']) && is_numeric($this->data['longitude']) ? (float) $this->data['longitude'] : null;
    }

    /**
     * Whether the locker is temporarily out of service. Then every free
     * count is 0.
     *
     * @return bool
     */
    public function isBlocked()
    {
        return !empty($this->data['is_blocked']);
    }

    /**
     * Until when a blocked locker is out of service (ISO 8601), or an empty
     * string.
     *
     * @return string
     */
    public function getBlockedUntil()
    {
        return $this->string('blocked_until');
    }

    /**
     * The box sizes present in the locker, smallest first as listed.
     *
     * @return LockerBoxSize[]
     */
    public function getBoxSizes()
    {
        return $this->boxSizes;
    }

    /**
     * Forecast free boxes per size by the end of a day: one entry for every
     * box size the locker has, 0 when the forecast leaves a size out.
     *
     * @param string $day DAY_TODAY or DAY_TOMORROW
     * @return array<string, int> size => free boxes
     */
    public function getFreeBoxes($day = self::DAY_TOMORROW)
    {
        $forecast = $this->forecast($day);
        $out = array();

        foreach ($this->boxSizes as $box) {
            $size = $box->getSize();
            $out[$size] = isset($forecast[$size]) && is_numeric($forecast[$size]) ? max(0, (int) $forecast[$size]) : 0;
        }

        return $out;
    }

    /**
     * The date a forecast is for (Y-m-d), or an empty string.
     *
     * @param string $day DAY_TODAY or DAY_TOMORROW
     * @return string
     */
    public function getForecastDate($day = self::DAY_TOMORROW)
    {
        $forecast = $this->forecast($day);

        return isset($forecast['date']) && is_scalar($forecast['date']) ? (string) $forecast['date'] : '';
    }

    /**
     * The answer as the API sent it.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $day
     * @return array
     */
    private function forecast($day)
    {
        $availability = isset($this->data['availability']) && is_array($this->data['availability'])
            ? $this->data['availability']
            : array();

        return isset($availability[$day]) && is_array($availability[$day]) ? $availability[$day] : array();
    }

    /**
     * @param string $key
     * @return string
     */
    private function string($key)
    {
        return isset($this->data[$key]) && is_scalar($this->data[$key]) ? trim((string) $this->data[$key]) : '';
    }
}
