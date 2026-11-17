<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\CourierRequest;

/**
 * Courier Requests Resource
 *
 * Ask Pigeon Express to send a courier to collect from an address, read a
 * request back and cancel it.
 *
 * The API has no endpoint that lists courier requests: a request is read back
 * by its number only, so a caller that needs a list keeps the numbers itself.
 */
class CourierRequests extends AbstractResource
{
    /**
     * Create a courier request (POST /courier-requests).
     *
     * Required: `pickup_city_id`, `requested_pickup_date` (Y-m-d),
     * `requested_pickup_time_type` (interval or specific_time) and
     * `requested_pickup_time_from`, plus `requested_pickup_time_to` for an
     * interval. `pickup_street_id` is required unless `pickup_additional_info`
     * is sent. Pass `shipment_references` to attach registered shipments; two
     * or more make a groupage. The body is sent as given.
     *
     * Deduplicated by the API like a shipment create: a 409 (an identical
     * request still running) is repeated with the same body, and a replayed
     * answer is reported by {@see getLastReplay()}. A replay of an earlier
     * request may describe a request that has been cancelled since, so read it
     * back with {@see get()} before relying on it.
     *
     * @param array|\PigeonExpress\Contracts\Arrayable $data
     * @return CourierRequest
     * @throws \PigeonExpress\Exceptions\ConflictException Still 409 after the repeats: the outcome is unknown
     * @throws \PigeonExpress\Exceptions\ValidationException
     */
    public function create($data)
    {
        $response = $this->sendWrite('POST', 'courier-requests', $this->toArray($data));

        return $this->toRequest($response->getData('data'));
    }

    /**
     * Get a courier request by its number (GET /courier-requests/{number}).
     *
     * @param string $requestNumber
     * @return CourierRequest
     * @throws \PigeonExpress\Exceptions\NotFoundException For a number this account does not have
     */
    public function get($requestNumber)
    {
        $response = $this->getClient()->get('courier-requests/' . rawurlencode((string) $requestNumber));

        return $this->toRequest($response->getData('data'));
    }

    /**
     * Cancel a courier request (POST /courier-requests/{number}/cancel).
     *
     * Refused with 422 once the request is completed, cancelled or failed.
     * A 409 is repeated as on create.
     *
     * @param string $requestNumber
     * @return CourierRequest
     */
    public function cancel($requestNumber)
    {
        $response = $this->sendWrite(
            'POST',
            'courier-requests/' . rawurlencode((string) $requestNumber) . '/cancel'
        );

        return $this->toRequest($response->getData('data'));
    }

    /**
     * @param mixed $data
     * @return CourierRequest
     */
    private function toRequest($data)
    {
        return CourierRequest::fromArray(is_array($data) ? $data : array());
    }
}
