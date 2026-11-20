<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\LockerAvailability;

/**
 * Lockers Resource
 *
 * Parcel lockers (APS) by their code. The list of lockers itself is on the
 * offices resource ({@see Offices::lockers()}).
 */
class Lockers extends AbstractResource
{
    /**
     * A parcel locker's box sizes and the forecast of free boxes per size by
     * the end of today and of tomorrow (GET /lockers/{code}/availability).
     *
     * Looked up by the locker's `code` ({@see \PigeonExpress\DTO\Response\Office::getCode()}),
     * never its id. Answers 404 (NotFoundException) for an office code, an
     * inactive locker or an unknown code. A blocked locker comes back with
     * isBlocked() true and no free boxes.
     *
     * @param string $code
     * @return LockerAvailability
     * @throws \InvalidArgumentException For an empty code
     * @throws \PigeonExpress\Exceptions\NotFoundException
     */
    public function availability($code)
    {
        $code = is_scalar($code) ? trim((string) $code) : '';

        if ($code === '') {
            throw new \InvalidArgumentException('A locker code is required.');
        }

        $response = $this->getClient()->get('lockers/' . rawurlencode($code) . '/availability');
        $data = $response->getData('data');

        return LockerAvailability::fromArray(is_array($data) ? $data : array());
    }
}
