<?php

namespace PigeonExpress\Exceptions;

/**
 * Server Exception (5xx)
 *
 * Thrown when the API server returns an error.
 */
class ServerException extends PigeonExpressException
{
    /**
     * @param string $message
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Server error', $httpStatusCode = 500)
    {
        parent::__construct($message, $httpStatusCode);
    }
}
