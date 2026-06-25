<?php

namespace PigeonExpress\Exceptions;

/**
 * Rate Limit Exception (429)
 *
 * Thrown when API rate limit is exceeded.
 */
class RateLimitException extends PigeonExpressException
{
    /**
     * @var int
     */
    protected $retryAfter;

    /**
     * @param string $message
     * @param int $retryAfter Seconds to wait before retry
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Rate limit exceeded', $retryAfter = 60, $httpStatusCode = 429)
    {
        parent::__construct($message, $httpStatusCode);
        $this->retryAfter = (int) $retryAfter;
    }

    /**
     * Get seconds to wait before retry
     *
     * @return int
     */
    public function getRetryAfter()
    {
        return $this->retryAfter;
    }
}
