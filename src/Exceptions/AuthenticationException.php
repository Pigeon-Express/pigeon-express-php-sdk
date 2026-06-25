<?php

namespace PigeonExpress\Exceptions;

/**
 * Authentication Exception (401)
 *
 * Thrown when API credentials are invalid or missing.
 */
class AuthenticationException extends PigeonExpressException
{
    /**
     * @param string $message
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Authentication failed', $httpStatusCode = 401)
    {
        parent::__construct($message, $httpStatusCode);
    }
}
