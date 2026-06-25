<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Working Hours DTO
 *
 * Represents office working hours.
 */
class WorkingHours implements Arrayable
{
    /**
     * @var array|null Monday hours
     */
    private $monday;

    /**
     * @var array|null Tuesday hours
     */
    private $tuesday;

    /**
     * @var array|null Wednesday hours
     */
    private $wednesday;

    /**
     * @var array|null Thursday hours
     */
    private $thursday;

    /**
     * @var array|null Friday hours
     */
    private $friday;

    /**
     * @var array|null Saturday hours
     */
    private $saturday;

    /**
     * @var array|null Sunday hours
     */
    private $sunday;

    /**
     * Get Monday hours
     *
     * @return array|null ['open' => '09:00', 'close' => '18:00'] or null if closed
     */
    public function getMonday()
    {
        return $this->monday;
    }

    /**
     * Get Tuesday hours
     *
     * @return array|null
     */
    public function getTuesday()
    {
        return $this->tuesday;
    }

    /**
     * Get Wednesday hours
     *
     * @return array|null
     */
    public function getWednesday()
    {
        return $this->wednesday;
    }

    /**
     * Get Thursday hours
     *
     * @return array|null
     */
    public function getThursday()
    {
        return $this->thursday;
    }

    /**
     * Get Friday hours
     *
     * @return array|null
     */
    public function getFriday()
    {
        return $this->friday;
    }

    /**
     * Get Saturday hours
     *
     * @return array|null
     */
    public function getSaturday()
    {
        return $this->saturday;
    }

    /**
     * Get Sunday hours
     *
     * @return array|null
     */
    public function getSunday()
    {
        return $this->sunday;
    }

    /**
     * Check if open on a specific day
     *
     * @param string $day Day name (monday, tuesday, etc.)
     * @return bool
     */
    public function isOpenOn($day)
    {
        $day = strtolower($day);
        $hours = $this->$day;

        return $hours !== null && isset($hours['open']) && isset($hours['close']);
    }

    /**
     * Get hours for a specific day
     *
     * @param string $day Day name (monday, tuesday, etc.)
     * @return array|null
     */
    public function getHoursFor($day)
    {
        $day = strtolower($day);

        if (property_exists($this, $day)) {
            return $this->$day;
        }

        return null;
    }

    /**
     * Check if open 24/7 (typical for lockers)
     *
     * @return bool
     */
    public function is24Hours()
    {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

        foreach ($days as $day) {
            $hours = $this->$day;

            if ($hours === null) {
                return false;
            }

            if (!isset($hours['open']) || !isset($hours['close'])) {
                return false;
            }

            // Check for 24-hour operation (00:00 to 23:59 or similar)
            if ($hours['open'] !== '00:00' || !in_array($hours['close'], array('23:59', '24:00'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $hours = new self();
        $hours->monday = isset($data['monday']) ? $data['monday'] : null;
        $hours->tuesday = isset($data['tuesday']) ? $data['tuesday'] : null;
        $hours->wednesday = isset($data['wednesday']) ? $data['wednesday'] : null;
        $hours->thursday = isset($data['thursday']) ? $data['thursday'] : null;
        $hours->friday = isset($data['friday']) ? $data['friday'] : null;
        $hours->saturday = isset($data['saturday']) ? $data['saturday'] : null;
        $hours->sunday = isset($data['sunday']) ? $data['sunday'] : null;

        return $hours;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'monday' => $this->monday,
            'tuesday' => $this->tuesday,
            'wednesday' => $this->wednesday,
            'thursday' => $this->thursday,
            'friday' => $this->friday,
            'saturday' => $this->saturday,
            'sunday' => $this->sunday,
        );
    }
}
