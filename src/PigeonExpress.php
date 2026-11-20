<?php

namespace PigeonExpress;

use PigeonExpress\HttpClient\GuzzleHttpClient;
use PigeonExpress\HttpClient\HttpClientInterface;
use PigeonExpress\Resources\AdditionalServices;
use PigeonExpress\Resources\Addresses;
use PigeonExpress\Resources\Cities;
use PigeonExpress\Resources\CourierRequests;
use PigeonExpress\Resources\Lockers;
use PigeonExpress\Resources\Offices;
use PigeonExpress\Resources\Shipments;
use PigeonExpress\Resources\ShipmentStatuses;
use PigeonExpress\Resources\Streets;

/**
 * Pigeon Express SDK Client
 *
 * Main entry point for interacting with the Pigeon Express API.
 *
 * @property-read Offices $offices
 * @property-read Cities $cities
 * @property-read Streets $streets
 * @property-read Shipments $shipments
 * @property-read AdditionalServices $additionalServices
 * @property-read ShipmentStatuses $shipmentStatuses
 * @property-read CourierRequests $courierRequests
 * @property-read Addresses $addresses
 * @property-read Lockers $lockers
 */
class PigeonExpress
{
    /**
     * SDK version
     */
    const VERSION = '1.1.0';

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var array Resource instances cache
     */
    private $resources = array();

    /**
     * Create a new Pigeon Express client instance
     *
     * @param string $apiKey Your API key
     * @param string $apiSecret Your API secret
     * @param array $options Optional configuration options:
     *                       - base_url: API base URL (default: https://api.pigeonexpress.com)
     *                       - timeout: Request timeout in seconds (default: 30)
     *                       - debug: Enable debug mode (default: false)
     *
     * @throws \InvalidArgumentException If API key or secret is empty
     */
    public function __construct($apiKey, $apiSecret, array $options = array())
    {
        $this->config = new Configuration($apiKey, $apiSecret, $options);
        $this->httpClient = new GuzzleHttpClient($this->config);
    }

    /**
     * Get the configuration
     *
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the HTTP client
     *
     * @return HttpClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Set a custom HTTP client
     *
     * @param HttpClientInterface $httpClient
     * @return $this
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Clear cached resources so they use the new client
        $this->resources = array();

        return $this;
    }

    /**
     * Magic method to access resources as properties
     *
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        $method = $name;

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException(sprintf('Unknown resource: %s', $name));
    }

    /**
     * Get offices resource
     *
     * @return Offices
     */
    public function offices()
    {
        if (!isset($this->resources['offices'])) {
            $this->resources['offices'] = new Offices($this->httpClient);
        }

        return $this->resources['offices'];
    }

    /**
     * Get cities resource
     *
     * @return Cities
     */
    public function cities()
    {
        if (!isset($this->resources['cities'])) {
            $this->resources['cities'] = new Cities($this->httpClient);
        }

        return $this->resources['cities'];
    }

    /**
     * Get streets resource
     *
     * @return Streets
     */
    public function streets()
    {
        if (!isset($this->resources['streets'])) {
            $this->resources['streets'] = new Streets($this->httpClient);
        }

        return $this->resources['streets'];
    }

    /**
     * Get shipments resource
     *
     * @return Shipments
     */
    public function shipments()
    {
        if (!isset($this->resources['shipments'])) {
            $this->resources['shipments'] = new Shipments($this->httpClient);
        }

        return $this->resources['shipments'];
    }

    /**
     * Get additional services resource
     *
     * @return AdditionalServices
     */
    public function additionalServices()
    {
        if (!isset($this->resources['additionalServices'])) {
            $this->resources['additionalServices'] = new AdditionalServices($this->httpClient);
        }

        return $this->resources['additionalServices'];
    }

    /**
     * Get shipment statuses resource
     *
     * @return ShipmentStatuses
     */
    public function shipmentStatuses()
    {
        if (!isset($this->resources['shipmentStatuses'])) {
            $this->resources['shipmentStatuses'] = new ShipmentStatuses($this->httpClient);
        }

        return $this->resources['shipmentStatuses'];
    }

    /**
     * Get courier requests resource
     *
     * @return CourierRequests
     */
    public function courierRequests()
    {
        if (!isset($this->resources['courierRequests'])) {
            $this->resources['courierRequests'] = new CourierRequests($this->httpClient);
        }

        return $this->resources['courierRequests'];
    }

    /**
     * Get saved addresses resource
     *
     * @return Addresses
     */
    public function addresses()
    {
        if (!isset($this->resources['addresses'])) {
            $this->resources['addresses'] = new Addresses($this->httpClient);
        }

        return $this->resources['addresses'];
    }

    /**
     * Get parcel lockers resource (free boxes by locker code)
     *
     * @return Lockers
     */
    public function lockers()
    {
        if (!isset($this->resources['lockers'])) {
            $this->resources['lockers'] = new Lockers($this->httpClient);
        }

        return $this->resources['lockers'];
    }

    /**
     * Get SDK version
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::VERSION;
    }
}
