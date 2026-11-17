<?php

namespace PigeonExpress\DTO\Request;

use PigeonExpress\Contracts\Arrayable;

/**
 * Package Data DTO
 *
 * Represents a package with dimensions and weight.
 */
class PackageData implements Arrayable
{
    /**
     * @var float Weight in kg
     */
    private $weight;

    /**
     * @var float Length in cm
     */
    private $length;

    /**
     * @var float Width in cm
     */
    private $width;

    /**
     * @var float Height in cm
     */
    private $height;

    /**
     * @var string|null Package description
     */
    private $description;

    /**
     * @var bool Is fragile
     */
    private $isFragile = false;

    /**
     * @var array Fields with no typed setter, sent as given
     */
    private $extra = array();

    /**
     * Create package data
     *
     * @param float $weight Weight in kg (required)
     * @param float $length Length in cm (required)
     * @param float $width Width in cm (required)
     * @param float $height Height in cm (required)
     */
    public function __construct($weight, $length, $width, $height)
    {
        $this->weight = (float) $weight;
        $this->length = (float) $length;
        $this->width = (float) $width;
        $this->height = (float) $height;
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Get length
     *
     * @return float
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get width
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Get height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Check if fragile
     *
     * @return bool
     */
    public function isFragile()
    {
        return $this->isFragile;
    }

    /**
     * Set fragile flag
     *
     * @param bool $fragile
     * @return $this
     */
    public function setFragile($fragile = true)
    {
        $this->isFragile = (bool) $fragile;
        return $this;
    }

    /**
     * Set a field that has no typed setter. It is sent as given, after the
     * typed fields, so it wins over one of the same name.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->extra[(string) $key] = $value;
        return $this;
    }

    /**
     * Get the fields set with set().
     *
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        );

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->isFragile) {
            $data['is_fragile'] = true;
        }

        return array_merge($data, $this->extra);
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $package = new self(
            isset($data['weight']) ? $data['weight'] : 0,
            isset($data['length']) ? $data['length'] : 0,
            isset($data['width']) ? $data['width'] : 0,
            isset($data['height']) ? $data['height'] : 0
        );

        if (isset($data['description'])) {
            $package->setDescription($data['description']);
        }

        if (isset($data['is_fragile'])) {
            $package->setFragile($data['is_fragile']);
        }

        // Anything else is kept and sent as given rather than dropped.
        $known = array('weight', 'length', 'width', 'height', 'description', 'is_fragile');
        foreach ($data as $key => $value) {
            if (!in_array($key, $known, true)) {
                $package->set($key, $value);
            }
        }

        return $package;
    }
}
