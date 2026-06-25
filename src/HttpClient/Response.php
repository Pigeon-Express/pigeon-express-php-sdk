<?php

namespace PigeonExpress\HttpClient;

/**
 * HTTP Response Wrapper
 *
 * Wraps HTTP response data for consistent handling.
 */
class Response
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array|null
     */
    private $decodedBody;

    /**
     * @param int $statusCode
     * @param array $headers
     * @param string $body
     */
    public function __construct($statusCode, array $headers, $body)
    {
        $this->statusCode = (int) $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->decodedBody = null;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get a specific header value
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return is_array($value) ? $value[0] : $value;
            }
        }
        return $default;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get decoded JSON body
     *
     * @param bool $assoc Return associative array
     * @return array|object|null
     */
    public function json($assoc = true)
    {
        if ($this->decodedBody === null && !empty($this->body)) {
            $this->decodedBody = json_decode($this->body, $assoc);
        }
        return $this->decodedBody;
    }

    /**
     * Check if response is successful (2xx)
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is a client error (4xx)
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is a server error (5xx)
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500;
    }

    /**
     * Get data from response body
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getData($key = null, $default = null)
    {
        $data = $this->json();

        if ($key === null) {
            return $data;
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        // Support dot notation
        $keys = explode('.', $key);
        $value = $data;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
