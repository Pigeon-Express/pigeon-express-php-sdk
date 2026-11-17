<?php

namespace PigeonExpress\Resources;

use PigeonExpress\HttpClient\HttpClientInterface;
use PigeonExpress\Contracts\Arrayable;
use PigeonExpress\Exceptions\ConflictException;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\HttpClient\Response;

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
     * How a write answered with 409 is repeated: at most this many times,
     * waiting what the API asks for in between, within these bounds. The whole
     * exchange has to end well inside the 30 seconds for which the API answers
     * an identical request with the original result.
     */
    const CONFLICT_RETRIES = 2;
    const CONFLICT_WAIT_DEFAULT = 5;
    const CONFLICT_WAIT_MAX = 8;

    /**
     * What {@see getLastReplay()} says about the last write call.
     *
     * - REPLAY_NONE: the API acted on this request.
     * - REPLAY_REPEAT: the answer to a repeat made after a 409, so it is the
     *   result of the identical request that was still running when this one
     *   was sent. Nothing came after it.
     * - REPLAY_EARLIER: the stored answer of an identical request made earlier
     *   by another call. Something may have happened since, for example the
     *   shipment it created may have been cancelled.
     */
    const REPLAY_NONE = '';
    const REPLAY_REPEAT = 'repeat';
    const REPLAY_EARLIER = 'earlier';

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var string One of the REPLAY_* constants
     */
    private $lastReplay = self::REPLAY_NONE;

    /**
     * @var int|null Override for CONFLICT_RETRIES, null for the default
     */
    private $conflictRetries;

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
     * Whether the answer to the last write call made through this resource was
     * the API's replay of an identical request, and of which one.
     *
     * Read it straight after the call it is about: every write call resets it.
     * Read calls leave it alone.
     *
     * @return string One of the REPLAY_* constants
     */
    public function getLastReplay()
    {
        return $this->lastReplay;
    }

    /**
     * Whether the last write call was answered with a replayed result.
     *
     * @return bool
     */
    public function wasLastReplayed()
    {
        return $this->lastReplay !== self::REPLAY_NONE;
    }

    /**
     * Set how many times a write answered with 409 is repeated before the
     * ConflictException is thrown. 0 turns repeating off.
     *
     * @param int $retries
     * @return $this
     */
    public function setConflictRetries($retries)
    {
        $this->conflictRetries = max(0, (int) $retries);

        return $this;
    }

    /**
     * Send a write request, tracking a replayed answer and, when asked to,
     * repeating it while the API answers 409.
     *
     * A 409 says an identical request is still being processed, in practice
     * often the caller's own earlier attempt whose answer was lost to a
     * timeout. The repeat is sent at once with the very same body: it is only
     * safe while the API's 30 second deduplication lasts, and a retry scheduled
     * for minutes later would fall outside it.
     *
     * Still 409 after the repeats: the ConflictException is thrown, and the
     * caller must treat the outcome as unknown.
     *
     * @param string $method POST or PUT
     * @param string $path
     * @param array $body
     * @param bool $repeatConflicts
     * @return Response
     * @throws PigeonExpressException
     */
    protected function sendWrite($method, $path, array $body = array(), $repeatConflicts = true)
    {
        $this->lastReplay = self::REPLAY_NONE;

        $retries = $repeatConflicts
            ? ($this->conflictRetries !== null ? $this->conflictRetries : static::CONFLICT_RETRIES)
            : 0;

        for ($repeats = 0; ; $repeats++) {
            try {
                $response = strtoupper($method) === 'PUT'
                    ? $this->getClient()->put($path, $body)
                    : $this->getClient()->post($path, $body);
            } catch (ConflictException $e) {
                if ($repeats >= $retries) {
                    throw $e;
                }

                $wait = $e->getRetryAfter() > 0 ? $e->getRetryAfter() : static::CONFLICT_WAIT_DEFAULT;
                $this->pause(min($wait, static::CONFLICT_WAIT_MAX) * 1000000);
                continue;
            }

            if ($response->isDuplicateRequest()) {
                $this->lastReplay = $repeats > 0 ? self::REPLAY_REPEAT : self::REPLAY_EARLIER;
            }

            return $response;
        }
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
