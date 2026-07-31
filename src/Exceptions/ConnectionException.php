<?php

namespace PigeonExpress\Exceptions;

use Exception;

/**
 * Connection Exception
 *
 * Thrown when unable to connect to the API.
 */
class ConnectionException extends PigeonExpressException
{
    /**
     * A connection failure usually has no HTTP status, since the request never
     * completed, but when one is known it is passed through rather than dropped.
     *
     * @param string $message
     * @param int|null $httpStatusCode
     * @param Exception|null $previous
     */
    public function __construct($message = 'Connection failed', $httpStatusCode = null, Exception $previous = null)
    {
        parent::__construct($message, $httpStatusCode, $previous);
    }
}
