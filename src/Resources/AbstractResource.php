<?php

namespace PigeonExpress\Resources;

use PigeonExpress\HttpClient\HttpClientInterface;
use PigeonExpress\Contracts\Arrayable;

/**
 * Abstract Resource
 *
 * Base class for all API resources.
 */
abstract class AbstractResource
{
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * Create resource instance
     *
     * @param HttpClientInterface $httpClient
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get the HTTP client
     *
     * @return HttpClientInterface
     */
    protected function getClient()
    {
        return $this->httpClient;
    }

    /**
     * Build query array from filters, removing null values
     *
     * @param array $filters
     * @return array
     */
    protected function buildQuery(array $filters)
    {
        $query = array();

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    /**
     * Convert data to array if it implements Arrayable
     *
     * @param mixed $data
     * @return array
     */
    protected function toArray($data)
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if (is_array($data)) {
            return $data;
        }

        return array();
    }

    /**
     * Map array of data to DTO objects
     *
     * @param array $items
     * @param string $dtoClass
     * @return array
     */
    protected function mapToDto(array $items, $dtoClass)
    {
        $result = array();

        foreach ($items as $item) {
            $result[] = call_user_func(array($dtoClass, 'fromArray'), $item);
        }

        return $result;
    }
}
