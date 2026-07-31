<?php

namespace PigeonExpress\Resources;

use PigeonExpress\HttpClient\HttpClientInterface;
use PigeonExpress\Contracts\Arrayable;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\RateLimitException;

/**
 * Abstract Resource
 *
 * Base class for all API resources.
 */
abstract class AbstractResource
{
    /** The API refuses a larger page size on every listing endpoint. */
    const MAX_PER_PAGE = 100;

    /** Attempts per page before giving up. */
    const PAGE_ATTEMPTS = 3;

    /** Pause between attempts, microseconds. */
    const RETRY_DELAY_US = 1500000;

    /**
     * Pause between successive pages, microseconds.
     *
     * Zero by default. Some of these listings are walked during checkout, for
     * example a street lookup while pricing an address delivery, and an
     * artificial sleep per page would stall the page load. Rate limiting is
     * handled reactively instead: a 429 is obeyed for exactly as long as the API
     * asks via Retry-After, which is more accurate than guessing a delay up
     * front. Callers doing bulk work can still pass a delay explicitly.
     */
    const PAGE_DELAY_US = 0;

    /** Fallback wait when a 429 arrives without a usable Retry-After. */
    const RATE_LIMIT_FALLBACK_SECONDS = 65;

    /** Hard stop so a bad last_page value cannot loop forever. */
    const MAX_PAGES = 500;

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
     * Walk every page of a paginated listing endpoint and return the raw rows.
     *
     * Listing endpoints cap per_page at 100, so anything that promises "all"
     * has to paginate. Returning only the first page silently truncates, which
     * for a location sync means quietly losing offices.
     *
     * Transient failures are retried per page rather than restarting the whole
     * walk, and a 429 waits for as long as the API asks before trying again.
     *
     * @param string $path Endpoint path, e.g. 'offices'
     * @param array $filters Query filters, page and per_page are managed here
     * @param array $options pageDelayUs, attempts, maxPages overrides
     * @return array Raw row arrays from every page, in order
     * @throws PigeonExpressException When a page still fails after all attempts
     */
    protected function paginate($path, array $filters = array(), array $options = array())
    {
        $query = $this->buildQuery($filters);

        $perPage = isset($query['per_page'])
            ? min((int) $query['per_page'], static::MAX_PER_PAGE)
            : static::MAX_PER_PAGE;

        $pageDelayUs = isset($options['pageDelayUs']) ? (int) $options['pageDelayUs'] : static::PAGE_DELAY_US;
        $attempts    = isset($options['attempts']) ? (int) $options['attempts'] : static::PAGE_ATTEMPTS;
        $maxPages    = isset($options['maxPages']) ? (int) $options['maxPages'] : static::MAX_PAGES;

        $rows = array();
        $seenIds = array();
        $page = 1;
        $lastPage = 1;

        do {
            $query['page'] = $page;
            $query['per_page'] = $perPage;

            $response = $this->getPageWithRetry($path, $query, $attempts);
            $data = $response->getData('data');

            if (!is_array($data) || empty($data)) {
                break;
            }

            // Some endpoints keep serving the same page when asked for one past
            // the end. Stop rather than accumulate duplicates forever.
            $firstId = isset($data[0]['id']) ? (string) $data[0]['id'] : null;
            if ($firstId !== null && isset($seenIds[$firstId])) {
                break;
            }
            if ($firstId !== null) {
                $seenIds[$firstId] = true;
            }

            foreach ($data as $row) {
                $rows[] = $row;
            }

            $meta = $response->getData('meta');
            $lastPage = is_array($meta) && isset($meta['last_page']) ? (int) $meta['last_page'] : 1;
            $page++;

            if ($page <= $lastPage && $page <= $maxPages && $pageDelayUs > 0) {
                $this->pause($pageDelayUs);
            }
        } while ($page <= $lastPage && $page <= $maxPages);

        return $rows;
    }

    /**
     * Fetch a single page, retrying transient failures and honouring 429 waits.
     *
     * @param string $path
     * @param array $query
     * @param int $attempts
     * @return \PigeonExpress\HttpClient\Response
     * @throws PigeonExpressException
     */
    private function getPageWithRetry($path, array $query, $attempts)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->getClient()->get($path, $query);
            } catch (RateLimitException $e) {
                $lastException = $e;
                $wait = $e->getRetryAfter() > 0 ? $e->getRetryAfter() : static::RATE_LIMIT_FALLBACK_SECONDS;
                if ($attempt < $attempts) {
                    $this->pause($wait * 1000000);
                }
            } catch (PigeonExpressException $e) {
                $lastException = $e;
                if ($attempt < $attempts) {
                    $this->pause(static::RETRY_DELAY_US);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Sleep for the given number of microseconds.
     *
     * The single place this class waits, so a test can subclass and no-op it
     * rather than spending real seconds proving that a retry happened.
     *
     * @param int $microseconds
     * @return void
     */
    protected function pause($microseconds)
    {
        if ($microseconds > 0) {
            usleep((int) $microseconds);
        }
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
