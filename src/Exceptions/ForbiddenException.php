<?php

namespace PigeonExpress\Exceptions;

/**
 * Forbidden Exception (403)
 *
 * Thrown when access to a resource is denied.
 */
class ForbiddenException extends PigeonExpressException
{
    /**
     * @param string $message
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Access forbidden', $httpStatusCode = 403)
    {
        parent::__construct($message, $httpStatusCode);
    }
}
