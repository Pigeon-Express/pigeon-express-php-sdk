<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use PigeonExpress\Configuration;
use PigeonExpress\Exceptions\ConflictException;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\HttpClient\GuzzleHttpClient;
use PigeonExpress\HttpClient\Response;

/**
 * How the HTTP client turns the API's answers into exceptions and flags,
 * with Guzzle's own mock handler in place of the network.
 */
class GuzzleHttpClientTest extends TestCase
{
    protected function setUp(): void
    {
        // The rest of the suite runs without a composer install; this needs Guzzle.
        if (!class_exists(MockHandler::class)) {
            $this->markTestSkipped('guzzlehttp/guzzle is not installed');
        }
    }

    private function client(PsrResponse ...$responses): GuzzleHttpClient
    {
        $guzzle = new Client([
            'handler'     => HandlerStack::create(new MockHandler($responses)),
            'http_errors' => false,
        ]);

        return new GuzzleHttpClient(new Configuration('key', 'secret'), $guzzle);
    }

    private function conflictBody(): string
    {
        return (string) json_encode([
            'success' => false,
            'message' => 'Идентична заявка е в процес на обработка.',
        ]);
    }

    public function testAConflictCarriesRetryAfter(): void
    {
        $client = $this->client(new PsrResponse(409, ['Retry-After' => '5'], $this->conflictBody()));

        try {
            $client->post('shipments', ['receiver_name' => 'Иван']);
            $this->fail('expected a ConflictException');
        } catch (ConflictException $e) {
            $this->assertSame(5, $e->getRetryAfter());
            $this->assertSame(409, $e->getHttpStatusCode());
            $this->assertSame('Идентична заявка е в процес на обработка.', $e->getMessage());
        }
    }

    public function testAConflictWithoutRetryAfterSaysZero(): void
    {
        $client = $this->client(new PsrResponse(409, [], $this->conflictBody()));

        try {
            $client->put('shipments/1', []);
            $this->fail('expected a ConflictException');
        } catch (ConflictException $e) {
            $this->assertSame(0, $e->getRetryAfter(), 'the caller applies its own default');
        }
    }

    public function testAConflictIsStillAPigeonExpressException(): void
    {
        // Callers catching the base exception keep working.
        $this->expectException(PigeonExpressException::class);

        $this->client(new PsrResponse(409, [], $this->conflictBody()))->post('shipments', []);
    }

    public function testARateLimitIsNotMistakenForAConflict(): void
    {
        $this->expectException(RateLimitException::class);

        $this->client(new PsrResponse(429, ['Retry-After' => '30'], '{"success":false,"message":"x"}'))->get('offices');
    }

    public function testTheDuplicateRequestHeaderIsExposed(): void
    {
        $response = $this->client(
            new PsrResponse(201, ['X-Duplicate-Request' => 'true'], '{"success":true,"data":{}}')
        )->post('shipments', []);

        $this->assertTrue($response->isDuplicateRequest());
    }

    public function testAnOrdinaryAnswerIsNoDuplicate(): void
    {
        $response = $this->client(new PsrResponse(201, [], '{"success":true,"data":{}}'))->post('shipments', []);

        $this->assertFalse($response->isDuplicateRequest());
    }

    public function testTheDuplicateHeaderIsReadWhateverItsCase(): void
    {
        $this->assertTrue((new Response(200, ['x-duplicate-request' => ['TRUE']], ''))->isDuplicateRequest());
        $this->assertFalse((new Response(200, ['X-Duplicate-Request' => 'false'], ''))->isDuplicateRequest());
    }
}
