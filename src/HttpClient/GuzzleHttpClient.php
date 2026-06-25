<?php

namespace PigeonExpress\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PigeonExpress\Configuration;
use PigeonExpress\Exceptions\AuthenticationException;
use PigeonExpress\Exceptions\ConnectionException;
use PigeonExpress\Exceptions\ForbiddenException;
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\Exceptions\ServerException;
use PigeonExpress\Exceptions\ValidationException;

/**
 * Guzzle HTTP Client Implementation
 */
class GuzzleHttpClient implements HttpClientInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Configuration $config
     * @param Client|null $client
     */
    public function __construct(Configuration $config, Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?: new Client(array(
            'base_uri' => rtrim($config->getBaseUrl(), '/') . '/' . Configuration::API_VERSION . '/',
            'timeout' => $config->getTimeout(),
            'http_errors' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function get($url, array $query = array(), array $headers = array())
    {
        return $this->request('GET', $url, array(
            'query' => $query,
            'headers' => $headers,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function post($url, array $data = array(), array $headers = array())
    {
        return $this->request('POST', $url, array(
            'json' => $data,
            'headers' => $headers,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function put($url, array $data = array(), array $headers = array())
    {
        return $this->request('PUT', $url, array(
            'json' => $data,
            'headers' => $headers,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($url, array $headers = array())
    {
        return $this->request('DELETE', $url, array(
            'headers' => $headers,
        ));
    }

    /**
     * Send HTTP request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return Response
     * @throws PigeonExpressException
     */
    private function request($method, $url, array $options = array())
    {
        $options = $this->prepareOptions($options);

        try {
            $response = $this->client->request($method, $url, $options);

            $wrappedResponse = new Response(
                $response->getStatusCode(),
                $response->getHeaders(),
                (string) $response->getBody()
            );

            $this->handleErrors($wrappedResponse);

            return $wrappedResponse;

        } catch (ConnectException $e) {
            throw new ConnectionException(
                'Failed to connect to Pigeon Express API: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $wrappedResponse = new Response(
                    $response->getStatusCode(),
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
                $this->handleErrors($wrappedResponse);
            }

            throw new ConnectionException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Prepare request options with authentication
     *
     * @param array $options
     * @return array
     */
    private function prepareOptions(array $options)
    {
        $defaultHeaders = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->config->getApiKey(),
            'X-API-Secret' => $this->config->getApiSecret(),
        );

        if (isset($options['headers'])) {
            $options['headers'] = array_merge($defaultHeaders, $options['headers']);
        } else {
            $options['headers'] = $defaultHeaders;
        }

        $options['timeout'] = $this->config->getTimeout();

        return $options;
    }

    /**
     * Handle API error responses
     *
     * @param Response $response
     * @throws PigeonExpressException
     */
    private function handleErrors(Response $response)
    {
        if ($response->isSuccessful()) {
            return;
        }

        $statusCode = $response->getStatusCode();
        $data = $response->json();
        $message = isset($data['message']) ? $data['message'] : 'Unknown error';
        $errors = isset($data['errors']) ? $data['errors'] : array();

        switch ($statusCode) {
            case 401:
                throw new AuthenticationException($message, $statusCode);

            case 403:
                throw new ForbiddenException($message, $statusCode);

            case 404:
                throw new NotFoundException($message, $statusCode);

            case 422:
                throw new ValidationException($message, $errors, $statusCode);

            case 429:
                $retryAfter = $response->getHeader('Retry-After', 60);
                throw new RateLimitException($message, (int) $retryAfter, $statusCode);

            default:
                if ($response->isServerError()) {
                    throw new ServerException($message, $statusCode);
                }

                throw new PigeonExpressException($message, $statusCode);
        }
    }
}
