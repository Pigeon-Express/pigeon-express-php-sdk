<?php

namespace PigeonExpress\Exceptions;

/**
 * Conflict Exception (409)
 *
 * Thrown when an identical request (same credentials, path and body) is still
 * being processed by the API. It is not a refusal: the earlier request may yet
 * succeed. The API asks for the same request to be repeated after Retry-After
 * seconds, and answers a repeat of a request that already succeeded within the
 * last 30 seconds with the original result instead of acting twice.
 *
 * When this escapes a write call the outcome is unknown, so a caller must not
 * assume nothing happened.
 */
class ConflictException extends PigeonExpressException
{
    /**
     * @var int
     */
    protected $retryAfter;

    /**
     * @param string $message
     * @param int $retryAfter Seconds the API asks to wait, 0 when it did not say
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Identical request in progress', $retryAfter = 0, $httpStatusCode = 409)
    {
        parent::__construct($message, $httpStatusCode);
        $this->retryAfter = max(0, (int) $retryAfter);
    }

    /**
     * Seconds to wait before repeating the request, 0 when the API sent none.
     *
     * @return int
     */
    public function getRetryAfter()
    {
        return $this->retryAfter;
    }
}
