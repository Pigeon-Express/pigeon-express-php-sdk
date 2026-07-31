<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PigeonExpress\Exceptions\PigeonExpressException;
use PigeonExpress\Exceptions\RateLimitException;
use PigeonExpress\HttpClient\HttpClientInterface;
use PigeonExpress\HttpClient\Response;
use PigeonExpress\Resources\Cities;
use PigeonExpress\Resources\Offices;
use PigeonExpress\Resources\Streets;

/**
 * Listing endpoints cap per_page at 100. Returning only the first page silently
 * truncates, which for a location sync means quietly losing offices. These tests
 * pin the pagination walk, the retry behaviour and the endpoints actually called.
 */
class PaginationTest extends TestCase
{
    public function testOfficesWalksEveryPage(): void
    {
        $client = new RecordingHttpClient([
            $this->page(array_fill(0, 100, ['id' => 1]), 1, 3),
            $this->page(array_fill(0, 100, ['id' => 2]), 2, 3),
            $this->page(array_fill(0, 53, ['id' => 3]), 3, 3),
        ]);

        $offices = (new Offices($client))->all();

        $this->assertCount(253, $offices, 'all three pages must be returned, not just the first');
        $this->assertCount(3, $client->calls);
    }

    public function testOfficesRequestsTheDocumentedEndpointNotTheNonExistentSearchOne(): void
    {
        $client = new RecordingHttpClient([$this->page([['id' => 1]], 1, 1)]);

        (new Offices($client))->search(['name' => 'Sofia']);

        $this->assertSame('offices', $client->calls[0]['path'], '/offices/search does not exist');
        $this->assertSame('Sofia', $client->calls[0]['query']['name']);
    }

    public function testPerPageIsCappedAtTheApiMaximum(): void
    {
        $client = new RecordingHttpClient([$this->page([['id' => 1]], 1, 1)]);

        (new Offices($client))->all(['per_page' => 5000]);

        $this->assertSame(100, $client->calls[0]['query']['per_page']);
    }

    public function testTypeFiltersMapToQueryParams(): void
    {
        $client = new RecordingHttpClient([
            $this->page([['id' => 1]], 1, 1),
            $this->page([['id' => 2]], 1, 1),
        ]);

        $resource = new Offices($client);
        $resource->lockers();
        $resource->offices();

        $this->assertSame('locker', $client->calls[0]['query']['type']);
        $this->assertSame('office', $client->calls[1]['query']['type']);
    }

    public function testRepeatedFirstRowStopsTheWalk(): void
    {
        // Some endpoints keep serving the same page past the end. Without this
        // guard the loop would accumulate duplicates until MAX_PAGES.
        $client = new RecordingHttpClient([
            $this->page([['id' => 'a']], 1, 99),
            $this->page([['id' => 'a']], 2, 99),
        ]);

        $offices = (new Offices($client))->all();

        $this->assertCount(1, $offices);
        $this->assertCount(2, $client->calls);
    }

    public function testCitiesWalksEveryPage(): void
    {
        $client = new RecordingHttpClient([
            $this->page(array_fill(0, 100, ['id' => 1]), 1, 2),
            $this->page(array_fill(0, 12, ['id' => 2]), 2, 2),
        ]);

        $this->assertCount(112, (new Cities($client))->all());
    }

    public function testCityFindUsesTheByIdEndpoint(): void
    {
        $client = new RecordingHttpClient([
            new Response(200, [], json_encode(['success' => true, 'data' => ['id' => 759, 'name' => 'Sofia']])),
        ]);

        $city = (new Cities($client))->find(759);

        $this->assertSame('cities/759', $client->calls[0]['path'], 'must not list every city to find one');
        $this->assertNotNull($city);
    }

    public function testStreetsAreScopedToACity(): void
    {
        $client = new RecordingHttpClient([$this->page([['id' => 1]], 1, 1)]);

        (new Streets($client))->searchInCity(759, 'Vitosha');

        $this->assertSame('cities/759/streets', $client->calls[0]['path']);
        $this->assertSame('Vitosha', $client->calls[0]['query']['name']);
    }

    public function testShortStreetQueryIsNotSentAsAFilter(): void
    {
        // The API rejects a name shorter than 2 characters.
        $client = new RecordingHttpClient([$this->page([['id' => 1]], 1, 1)]);

        (new Streets($client))->searchInCity(759, 'V');

        $this->assertArrayNotHasKey('name', $client->calls[0]['query']);
    }

    public function testStreetSearchWithoutACityIsRefused(): void
    {
        $this->expectException(PigeonExpressException::class);
        $this->expectExceptionMessageMatches('/city_id/');

        (new Streets(new RecordingHttpClient([])))->search(['name' => 'Vitosha']);
    }

    public function testCrossCityStreetSearchIsRefusedWithAUsefulMessage(): void
    {
        $this->expectException(PigeonExpressException::class);
        $this->expectExceptionMessageMatches('/searchInCity/');

        (new Streets(new RecordingHttpClient([])))->searchByName('Vitosha');
    }

    public function testATransientFailureRetriesTheSamePage(): void
    {
        $client = new RecordingHttpClient([
            new PigeonExpressException('boom'),
            $this->page([['id' => 1]], 1, 1),
        ]);

        $offices = (new SleeplessOffices($client))->all();

        $this->assertCount(1, $offices);
        $this->assertCount(2, $client->calls, 'the failed page is retried, not abandoned');
    }

    public function testRateLimitIsRetriedAfterWaiting(): void
    {
        $client = new RecordingHttpClient([
            new RateLimitException('slow down', 0),
            $this->page([['id' => 1]], 1, 1),
        ]);

        $this->assertCount(1, (new SleeplessOffices($client))->all());
    }

    public function testAPageThatKeepsFailingSurfacesTheError(): void
    {
        $client = new RecordingHttpClient([
            new PigeonExpressException('boom'),
            new PigeonExpressException('boom'),
            new PigeonExpressException('boom'),
        ]);

        $this->expectException(PigeonExpressException::class);
        (new SleeplessOffices($client))->all();
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function page(array $rows, int $currentPage, int $lastPage): Response
    {
        return new Response(200, [], (string) json_encode([
            'success' => true,
            'data'    => $rows,
            'meta'    => ['current_page' => $currentPage, 'last_page' => $lastPage],
        ]));
    }
}

/**
 * Offices with the retry waits removed, so retry behaviour can be asserted
 * without the suite spending real seconds asleep.
 */
class SleeplessOffices extends Offices
{
    protected function pause($microseconds)
    {
        // Deliberately does nothing.
    }
}

/**
 * Replays a queued script of responses and exceptions, recording what was asked for.
 */
class RecordingHttpClient implements HttpClientInterface
{
    /** @var array<int, array{path: string, query: array}> */
    public $calls = [];

    /** @var array<int, Response|\Throwable> */
    private $script;

    public function __construct(array $script)
    {
        $this->script = $script;
    }

    public function get($url, array $query = [], array $headers = [])
    {
        $this->calls[] = ['path' => $url, 'query' => $query];

        $next = array_shift($this->script);
        if ($next instanceof \Throwable) {
            throw $next;
        }
        if ($next === null) {
            return new Response(200, [], (string) json_encode(['success' => true, 'data' => []]));
        }

        return $next;
    }

    public function post($url, array $data = [], array $headers = [])
    {
        throw new \LogicException('not used');
    }

    public function put($url, array $data = [], array $headers = [])
    {
        throw new \LogicException('not used');
    }

    public function delete($url, array $headers = [])
    {
        throw new \LogicException('not used');
    }
}
