<?php

namespace PigeonExpress;

/**
 * SDK Configuration
 *
 * Holds API credentials and connection settings.
 */
class Configuration
{
    /**
     * Default API base URL
     */
    const DEFAULT_BASE_URL = 'https://api.pigeonexpress.com';

    /**
     * Default request timeout in seconds
     */
    const DEFAULT_TIMEOUT = 30;

    /**
     * API version
     */
    const API_VERSION = 'v1';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiSecret;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @param string $apiKey
     * @param string $apiSecret
     * @param array $options Optional settings: base_url, timeout
     */
    public function __construct($apiKey, $apiSecret, array $options = array())
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;

        $baseUrl = isset($options['base_url']) ? $options['base_url'] : null;
        $timeout = isset($options['timeout']) ? $options['timeout'] : null;

        $this->baseUrl = $baseUrl !== null ? rtrim($baseUrl, '/') : self::DEFAULT_BASE_URL;
        $this->timeout = $timeout !== null ? (int) $timeout : self::DEFAULT_TIMEOUT;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Get the full API endpoint URL
     *
     * @param string $path
     * @return string
     */
    public function getApiUrl($path)
    {
        $path = ltrim($path, '/');
        return $this->baseUrl . '/' . self::API_VERSION . '/' . $path;
    }

    /**
     * Set base URL
     *
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Set timeout
     *
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
        return $this;
    }
}
