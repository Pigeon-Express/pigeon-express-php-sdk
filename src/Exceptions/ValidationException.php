<?php

namespace PigeonExpress\Exceptions;

/**
 * Validation Exception (422)
 *
 * Thrown when request validation fails.
 */
class ValidationException extends PigeonExpressException
{
    /**
     * @var array
     */
    protected $errors;

    /**
     * @param string $message
     * @param array $errors
     * @param int $httpStatusCode
     */
    public function __construct($message = 'Validation failed', array $errors = array(), $httpStatusCode = 422)
    {
        parent::__construct($message, $httpStatusCode);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if there are validation errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get errors for a specific field
     *
     * @param string $field
     * @return array
     */
    public function getFieldErrors($field)
    {
        return isset($this->errors[$field]) ? (array) $this->errors[$field] : array();
    }

    /**
     * Get first error for a specific field
     *
     * @param string $field
     * @return string|null
     */
    public function getFirstFieldError($field)
    {
        $errors = $this->getFieldErrors($field);
        return !empty($errors) ? $errors[0] : null;
    }
}
