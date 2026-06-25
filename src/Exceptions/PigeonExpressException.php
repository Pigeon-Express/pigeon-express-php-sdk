<?php

namespace PigeonExpress\Exceptions;

use Exception;

/**
 * Base Exception for Pigeon Express SDK
 */
class PigeonExpressException extends Exception
{
    /**
     * @var int|null
     */
    protected $httpStatusCode;

    /**
     * @param string $message
     * @param int|null $httpStatusCode
     * @param Exception|null $previous
     */
    public function __construct($message = '', $httpStatusCode = null, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Get HTTP status code
     *
     * @return int|null
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}
