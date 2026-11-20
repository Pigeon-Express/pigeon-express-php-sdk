<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Response\LockerAvailability;
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\PigeonExpress;
use PigeonExpress\Resources\Lockers;
use PigeonExpress\Resources\Offices;

/**
 * The nearest offices search and a parcel locker's free boxes. Shapes are
 * taken from the API documentation (v1.0.32).
 */
class NearestAndLockersTest extends TestCase
{
    public function testNearestSendsFixedNotationAndReadsTheDistance(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => [
            [
                'id' => 28,
                'name' => 'Офис Варна Център',
                'code' => 'VAR-001',
                'type' => 'office',
                'latitude' => '43.2140504',
                'longitude' => '27.9147333',
                'distance_km' => 0.01,
            ],
            ['id' => 31, 'name' => 'Офис Варна Чайка', 'type' => 'office', 'distance_km' => '1.46'],
            ['id' => 40, 'name' => 'Без разстояние', 'type' => 'office'],
            'not a row',
        ]])]);

        $offices = (new Offices($client))->nearest(0.00001, 27.9147, Offices::NEAREST_OFFICE, 20);

        $this->assertSame('GET', $client->calls[0]['method']);
        $this->assertSame('offices/nearest', $client->calls[0]['path']);
        $this->assertSame(
            ['latitude' => '0.000010', 'longitude' => '27.914700', 'type' => 'office', 'limit' => 20],
            $client->calls[0]['query'],
            'never 1.0E-5, which the API does not read as a number'
        );
        $this->assertCount(3, $offices);
        $this->assertSame(28, $offices[0]->getId());
        $this->assertSame('VAR-001', $offices[0]->getCode());
        $this->assertSame(0.01, $offices[0]->getDistanceKm());
        $this->assertSame(1.46, $offices[1]->getDistanceKm());
        $this->assertNull($offices[2]->getDistanceKm());
        $this->assertSame(0.01, $offices[0]->toArray()['distance_km']);
        $this->assertArrayNotHasKey('distance_km', $offices[2]->toArray(), 'other listings keep their shape');
    }

    public function testNearestDefaultsToFiveOfEitherKindAndClampsTheLimit(): void
    {
        $client = new ScriptedHttpClient();
        $offices = new Offices($client);

        $offices->nearest(43.2, 27.9);
        $offices->nearest(43.2, 27.9, 'aps', 0);
        $offices->nearest(43.2, 27.9, 'LOCKER', 99);

        $this->assertSame('all', $client->calls[0]['query']['type']);
        $this->assertSame(5, $client->calls[0]['query']['limit']);
        $this->assertSame('locker', $client->calls[1]['query']['type'], 'aps is taken for a locker');
        $this->assertSame(1, $client->calls[1]['query']['limit']);
        $this->assertSame('locker', $client->calls[2]['query']['type']);
        $this->assertSame(20, $client->calls[2]['query']['limit']);
    }

    /**
     * @dataProvider badPoints
     * @param mixed $lat
     * @param mixed $lng
     */
    public function testNearestRefusesABadPointWithoutAsking($lat, $lng): void
    {
        $client = new ScriptedHttpClient();

        try {
            (new Offices($client))->nearest($lat, $lng);
            $this->fail('expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertCount(0, $client->calls);
        }
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function badPoints(): array
    {
        return [
            'latitude above 90' => [90.5, 23.3],
            'longitude below -180' => [42.7, -180.1],
            'not a number' => ['abc', 23.3],
            'infinite' => [INF, 23.3],
            'not a number at all' => [NAN, 23.3],
        ];
    }

    public function testNearestRefusesAnUnknownType(): void
    {
        $client = new ScriptedHttpClient();

        $this->expectException(\InvalidArgumentException::class);
        (new Offices($client))->nearest(42.7, 23.3, 'warehouse');
    }

    /**
     * What GET /lockers/{code}/availability returns, trimmed.
     *
     * @return array<string, mixed>
     */
    private function locker(): array
    {
        return [
            'id' => 156,
            'name' => 'Бушидо АПС',
            'code' => '1001',
            'type' => 'locker',
            'address' => 'Пловдив, 4000, САНКТ ПЕТЕРБУРГ, № 131',
            'latitude' => '42.1425780',
            'longitude' => '24.7831650',
            'is_blocked' => false,
            'blocked_until' => null,
            'box_sizes' => [
                ['size' => 'small', 'length' => 10, 'width' => 10, 'height' => 10, 'max_weight' => 2, 'total' => 16],
                ['size' => 'Medium', 'length' => 30, 'width' => 30, 'height' => 30, 'max_weight' => null, 'total' => 15],
                ['size' => 'large', 'length' => 50, 'width' => 50, 'height' => 50, 'max_weight' => 20, 'total' => 4],
                ['length' => 1],
            ],
            'availability' => [
                'today' => ['date' => '2026-09-21', 'small' => 10, 'medium' => 15, 'large' => 3],
                'tomorrow' => ['date' => '2026-09-22', 'small' => 9, 'medium' => -2],
            ],
        ];
    }

    public function testAvailabilityIsLookedUpByCodeAndNormalised(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => $this->locker()])]);

        $locker = (new Lockers($client))->availability(' 1001 ');

        $this->assertSame('lockers/1001/availability', $client->calls[0]['path']);
        $this->assertSame(156, $locker->getId());
        $this->assertSame('1001', $locker->getCode());
        $this->assertSame('Бушидо АПС', $locker->getName());
        $this->assertFalse($locker->isBlocked());
        $this->assertSame('', $locker->getBlockedUntil());
        $this->assertSame(42.142578, $locker->getLatitude());
        $this->assertCount(3, $locker->getBoxSizes(), 'a row without a size is dropped');
        $this->assertSame('medium', $locker->getBoxSizes()[1]->getSize(), 'sizes are lower case');
        $this->assertNull($locker->getBoxSizes()[1]->getMaxWeight());
        $this->assertSame(2.0, $locker->getBoxSizes()[0]->getMaxWeight());
        $this->assertSame([50, 50, 50], $locker->getBoxSizes()[2]->getDimensions());
        $this->assertSame(4, $locker->getBoxSizes()[2]->getTotal());
        $this->assertSame(['small' => 10, 'medium' => 15, 'large' => 3], $locker->getFreeBoxes(LockerAvailability::DAY_TODAY));
        $this->assertSame(
            ['small' => 9, 'medium' => 0, 'large' => 0],
            $locker->getFreeBoxes(),
            'tomorrow by default; a size left out or below zero is 0'
        );
        $this->assertSame('2026-09-22', $locker->getForecastDate());
        $this->assertSame('1001', $locker->toArray()['code'], 'every field is kept');
    }

    public function testABlockedLockerSaysSo(): void
    {
        $data = $this->locker();
        $data['is_blocked'] = true;
        $data['blocked_until'] = '2026-09-25T10:00:00+03:00';

        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => $data])]);
        $locker = (new Lockers($client))->availability('1001');

        $this->assertTrue($locker->isBlocked());
        $this->assertSame('2026-09-25T10:00:00+03:00', $locker->getBlockedUntil());
    }

    public function testTheCodeIsUrlEncodedAndRequired(): void
    {
        $client = new ScriptedHttpClient();
        $lockers = new Lockers($client);

        $lockers->availability('A B/1');
        $this->assertSame('lockers/A%20B%2F1/availability', $client->calls[0]['path']);

        $this->expectException(\InvalidArgumentException::class);
        try {
            $lockers->availability('  ');
        } finally {
            $this->assertCount(1, $client->calls, 'nothing is sent without a code');
        }
    }

    public function testAnUnknownLockerSurfacesTheNotFound(): void
    {
        $client = new ScriptedHttpClient([new NotFoundException('АПС локърът не е намерен')]);

        $this->expectException(NotFoundException::class);
        (new Lockers($client))->availability('052');
    }

    public function testTheClientHandsOutTheLockersResource(): void
    {
        $sdk = new PigeonExpress('key', 'secret');

        $this->assertInstanceOf(Lockers::class, $sdk->lockers());
        $this->assertSame($sdk->lockers(), $sdk->lockers);
    }
}
