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
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\Exceptions\ServerException;
use PigeonExpress\Exceptions\ValidationException;
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

    public function testARefusalKeepsItsCodeAndExtraFields(): void
    {
        $body = (string) json_encode([
            'success' => false,
            'message' => 'Промяната ... може да бъде направена само в наш офис.',
            'code' => 'requires_office_payment',
            'errors' => [],
            'status_code' => 422,
            'price' => ['previous_total' => 4.5, 'new_total' => 6.25, 'paid_amount' => 4.5, 'amount_due' => 1.75],
        ]);
        $client = $this->client(new PsrResponse(422, [], $body));

        try {
            $client->post('shipments/1/actions/services', ['add' => ['shipment_review'], 'dry_run' => false]);
            $this->fail('expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getHttpStatusCode());
            $this->assertSame('requires_office_payment', $e->getErrorCode());
            $this->assertSame(1.75, $e->getResponseField('price.amount_due'));
            $this->assertSame('fallback', $e->getResponseField('price.missing', 'fallback'));
            $this->assertSame([], $e->getErrors());
            $this->assertSame('Промяната ... може да бъде направена само в наш офис.', $e->getMessage());
            $this->assertSame(422, $e->getResponseData()['status_code']);
        }
    }

    public function testAFieldValidationHasNoCode(): void
    {
        $body = (string) json_encode([
            'success' => false,
            'message' => 'Моля, коригирайте грешките във формуляра',
            'errors' => ['delivery_type' => ['типът доставка е задължително поле.']],
        ]);

        try {
            $this->client(new PsrResponse(422, [], $body))->post('shipments/1/actions/change-delivery', []);
            $this->fail('expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('', $e->getErrorCode());
            $this->assertSame(['типът доставка е задължително поле.'], $e->getFieldErrors('delivery_type'));
        }
    }

    public function testABackendUnavailableIsAServerErrorWithItsCode(): void
    {
        $body = (string) json_encode([
            'success' => false,
            'message' => 'Промяната не може да бъде направена в момента. Опитайте отново по-късно.',
            'code' => 'backend_unavailable',
            'errors' => [],
            'status_code' => 502,
        ]);

        try {
            $this->client(new PsrResponse(502, [], $body))->get('shipments/1/actions');
            $this->fail('expected a ServerException');
        } catch (ServerException $e) {
            $this->assertSame(502, $e->getHttpStatusCode());
            $this->assertSame('backend_unavailable', $e->getErrorCode());
        }
    }

    public function testAnUnknownRouteAnswerGivesItsMessage(): void
    {
        $body = (string) json_encode(['success' => false, 'error' => ['message' => 'Търсеният ресурс не съществува']]);

        try {
            $this->client(new PsrResponse(404, [], $body))->post('shipments/1/actions/edit', []);
            $this->fail('expected a NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertSame('Търсеният ресурс не съществува', $e->getMessage());
            $this->assertSame('', $e->getErrorCode());
        }
    }

    public function testAnErrorWithoutAJsonBodyHasNoData(): void
    {
        try {
            $this->client(new PsrResponse(500, [], '<html>oops</html>'))->get('offices');
            $this->fail('expected a ServerException');
        } catch (ServerException $e) {
            $this->assertSame([], $e->getResponseData());
            $this->assertSame('', $e->getErrorCode());
            $this->assertSame('Unknown error', $e->getMessage());
        }
    }

    public function testAConflictKeepsItsBodyToo(): void
    {
        try {
            $this->client(new PsrResponse(409, ['Retry-After' => '5'], $this->conflictBody()))->post('shipments', []);
            $this->fail('expected a ConflictException');
        } catch (ConflictException $e) {
            $this->assertFalse($e->getResponseData()['success']);
        }
    }
}
