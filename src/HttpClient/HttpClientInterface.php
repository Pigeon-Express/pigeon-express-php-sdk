<?php

namespace PigeonExpress\HttpClient;

use PigeonExpress\Exceptions\PigeonExpressException;

/**
 * HTTP Client Interface
 *
 * Contract for HTTP clients used by the SDK.
 */
interface HttpClientInterface
{
    /**
     * Send a GET request
     *
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return Response
     * @throws PigeonExpressException
     */
    public function get($url, array $query = array(), array $headers = array());

    /**
     * Send a POST request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return Response
     * @throws PigeonExpressException
     */
    public function post($url, array $data = array(), array $headers = array());

    /**
     * Send a PUT request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return Response
     * @throws PigeonExpressException
     */
    public function put($url, array $data = array(), array $headers = array());

    /**
     * Send a DELETE request
     *
     * @param string $url
     * @param array $headers
     * @return Response
     * @throws PigeonExpressException
     */
    public function delete($url, array $headers = array());
}
