<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PigeonExpress\HttpClient\HttpClientInterface;
use PigeonExpress\HttpClient\Response;

/**
 * Replays a queued script of responses and exceptions for every HTTP verb,
 * recording each call: method, path, query and body.
 */
class ScriptedHttpClient implements HttpClientInterface
{
    /** @var array<int, array{method: string, path: string, query: array, body: array}> */
    public $calls = [];

    /** @var array<int, Response|\Throwable> */
    private $script;

    /** @param array<int, Response|\Throwable> $script */
    public function __construct(array $script = [])
    {
        $this->script = $script;
    }

    /** @param mixed $data */
    public static function json($data, int $status = 200, array $headers = []): Response
    {
        return new Response($status, $headers, (string) json_encode($data));
    }

    public function get($url, array $query = [], array $headers = [])
    {
        return $this->next('GET', $url, $query, []);
    }

    public function post($url, array $data = [], array $headers = [])
    {
        return $this->next('POST', $url, [], $data);
    }

    public function put($url, array $data = [], array $headers = [])
    {
        return $this->next('PUT', $url, [], $data);
    }

    public function delete($url, array $headers = [])
    {
        return $this->next('DELETE', $url, [], []);
    }

    private function next(string $method, string $url, array $query, array $body): Response
    {
        $this->calls[] = ['method' => $method, 'path' => $url, 'query' => $query, 'body' => $body];

        $next = array_shift($this->script);
        if ($next instanceof \Throwable) {
            throw $next;
        }
        if ($next === null) {
            return self::json(['success' => true, 'data' => []]);
        }

        return $next;
    }
}
