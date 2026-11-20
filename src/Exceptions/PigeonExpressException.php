<?php

namespace PigeonExpress\Exceptions;

use Exception;

/**
 * Base Exception for Pigeon Express SDK
 *
 * An error answered by the API keeps its HTTP status
 * ({@see getHttpStatusCode()}) and its whole decoded body
 * ({@see getResponseData()}), so a caller can decide by the API's
 * machine-readable reason ({@see getErrorCode()}) and read the fields some
 * refusals carry, for example `price.amount_due` with
 * `requires_office_payment` or `active_reference_number` with
 * `already_redirected`. Never decide by the message: it is meant for people.
 */
class PigeonExpressException extends Exception
{
    /**
     * @var int|null
     */
    protected $httpStatusCode;

    /**
     * @var array The decoded error body, empty when there was none
     */
    protected $responseData = array();

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

    /**
     * Attach the decoded body of the API's error answer.
     *
     * @param array $data
     * @return $this
     */
    public function withResponseData(array $data)
    {
        $this->responseData = $data;

        return $this;
    }

    /**
     * The API's whole error answer, decoded: `message`, `code`, `errors`,
     * `status_code` and whatever a refusal adds (`price`,
     * `active_reference_number`, `conflicts`, ...). Empty for an error that
     * did not come with a JSON body, a connection failure above all.
     *
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * The API's machine-readable reason for the error (`code` in its
     * answer, e.g. `requires_office_payment`, `already_redirected`,
     * `not_found`, `backend_unavailable`), or an empty string when it gave
     * none. A field validation by the API itself has no code.
     *
     * @return string
     */
    public function getErrorCode()
    {
        $code = isset($this->responseData['code']) ? $this->responseData['code'] : '';

        return is_scalar($code) ? (string) $code : '';
    }

    /**
     * One field of the error answer, with dot notation for nested ones
     * (`price.amount_due`).
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getResponseField($key, $default = null)
    {
        $value = $this->responseData;

        foreach (explode('.', (string) $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
