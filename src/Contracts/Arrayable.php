<?php

namespace PigeonExpress\Contracts;

/**
 * Interface for objects that can be converted to array
 */
interface Arrayable
{
    /**
     * Convert the object to an array
     *
     * @return array
     */
    public function toArray();
}
