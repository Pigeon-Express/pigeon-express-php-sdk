<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Request\SearchStreetsRequest;
use PigeonExpress\Resources\Streets;

/**
 * Streets of a city, narrowed by name and by postcode.
 */
class StreetsTest extends TestCase
{
    private function page(array $rows): \PigeonExpress\HttpClient\Response
    {
        return ScriptedHttpClient::json(['success' => true, 'data' => $rows, 'meta' => ['last_page' => 1]]);
    }

    public function testAPostcodeIsSentAndReadBack(): void
    {
        $client = new ScriptedHttpClient([$this->page([
            ['id' => 406805, 'name' => '3-ТИ МАРТ', 'type' => 'улица', 'city_id' => 2556, 'postal_code' => ' 9000 '],
            ['id' => 406790, 'name' => 'АЛЕН МАК', 'type' => 'улица', 'city_id' => 2556, 'postal_code' => null],
        ])]);

        $streets = (new Streets($client))->byCity(2556, 'ма', ' 9000 ');

        $this->assertSame('cities/2556/streets', $client->calls[0]['path']);
        $this->assertSame('ма', $client->calls[0]['query']['name']);
        $this->assertSame('9000', $client->calls[0]['query']['postal_code']);
        $this->assertSame('9000', $streets[0]->getPostalCode());
        $this->assertNull($streets[1]->getPostalCode());
        $this->assertSame('9000', $streets[0]->toArray()['postal_code']);
    }

    public function testAPostcodeTheApiWouldRefuseIsLeftOut(): void
    {
        $client = new ScriptedHttpClient();
        $streets = new Streets($client);

        $streets->byCity(1, null, '4');
        $streets->byCity(1, null, '12345678901');
        $streets->byCity(1, null, '');
        $streets->searchInCity(1, 'Витоша', 5, '1000');

        $this->assertArrayNotHasKey('postal_code', $client->calls[0]['query'], 'below 2 characters');
        $this->assertArrayNotHasKey('postal_code', $client->calls[1]['query'], 'above 10 characters');
        $this->assertArrayNotHasKey('postal_code', $client->calls[2]['query']);
        $this->assertSame('1000', $client->calls[3]['query']['postal_code']);
        $this->assertSame('Витоша', $client->calls[3]['query']['name']);
    }

    public function testSearchTakesThePostcodeFromTheRequest(): void
    {
        $client = new ScriptedHttpClient();

        (new Streets($client))->search((new SearchStreetsRequest())->setCityId(68134)->setPostalCode('1000'));

        $this->assertSame('cities/68134/streets', $client->calls[0]['path']);
        $this->assertSame('1000', $client->calls[0]['query']['postal_code']);
    }
}
