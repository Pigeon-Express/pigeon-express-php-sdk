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
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = 'Connection failed', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, null, $previous);
    }
}
