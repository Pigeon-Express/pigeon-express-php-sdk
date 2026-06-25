<?php

namespace PigeonExpress\Exceptions;

/**
 * Not Found Exception (404)
 *
 * Thrown when a requested resource is not found.
 */
class NotFoundException extends PigeonExpressException
{
    /**
     * @param string $message
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Resource not found', $httpStatusCode = 404)
    {
        parent::__construct($message, $httpStatusCode);
    }
}
