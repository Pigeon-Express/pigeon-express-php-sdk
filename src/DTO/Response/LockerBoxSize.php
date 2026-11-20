<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * One box size of a parcel locker (`box_sizes` of
 * `GET /lockers/{code}/availability`): its inner size in cm, the heaviest
 * parcel it takes and how many boxes of it the locker has.
 */
class LockerBoxSize implements Arrayable
{
    const SIZE_SMALL = 'small';
    const SIZE_MEDIUM = 'medium';
    const SIZE_LARGE = 'large';

    /**
     * @var string
     */
    private $size = '';

    /**
     * @var int
     */
    private $length = 0;

    /**
     * @var int
     */
    private $width = 0;

    /**
     * @var int
     */
    private $height = 0;

    /**
     * @var float|null
     */
    private $maxWeight;

    /**
     * @var int
     */
    private $total = 0;

    /**
     * The size name: small, medium or large (lower case).
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Inner length, cm.
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Inner width, cm.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Inner height, cm.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * The three inner sides, cm, as listed.
     *
     * @return int[]
     */
    public function getDimensions()
    {
        return array($this->length, $this->width, $this->height);
    }

    /**
     * Heaviest parcel the box takes, kg, or null when the API gives no limit.
     *
     * @return float|null
     */
    public function getMaxWeight()
    {
        return $this->maxWeight;
    }

    /**
     * Boxes of this size in the locker, free and occupied.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $box = new self();
        $box->size = isset($data['size']) && is_scalar($data['size']) ? strtolower(trim((string) $data['size'])) : '';
        $box->length = isset($data['length']) && is_numeric($data['length']) ? max(0, (int) $data['length']) : 0;
        $box->width = isset($data['width']) && is_numeric($data['width']) ? max(0, (int) $data['width']) : 0;
        $box->height = isset($data['height']) && is_numeric($data['height']) ? max(0, (int) $data['height']) : 0;
        $box->maxWeight = isset($data['max_weight']) && is_numeric($data['max_weight']) ? (float) $data['max_weight'] : null;
        $box->total = isset($data['total']) && is_numeric($data['total']) ? max(0, (int) $data['total']) : 0;

        return $box;
    }

    /**
     * @return array{size: string, length: int, width: int, height: int, max_weight: float|null, total: int}
     */
    public function toArray()
    {
        return array(
            'size' => $this->size,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'max_weight' => $this->maxWeight,
            'total' => $this->total,
        );
    }
}
