<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Response\BulkTrackingEntry;
use PigeonExpress\Exceptions\ConflictException;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Resources\AbstractResource;
use PigeonExpress\Resources\Shipments;

/**
 * The shipment read-back, edit and bulk tracking calls, and the 409 / replay
 * handling the carrier's 30 second deduplication needs. Shapes are taken from
 * the live API docs and a sandbox read-back.
 */
class ShipmentsTest extends TestCase
{
    /** What GET /shipments/{ref} returns, trimmed. */
    private function details(): array
    {
        return [
            'reference_number'   => '459073686609',
            'external_reference' => 'ORDER-1',
            'status'             => ['code' => 'shipment_registered', 'name' => 'Създадена от подател'],
            'service_type'       => 'standard',
            'created_at'         => '2026-09-04T16:16:26+03:00',
            'delivery_date'      => null,
            'delivery'           => ['type' => 'office', 'office' => ['code' => '052'], 'address' => null],
            'receiver'           => ['name' => 'Иван Петров', 'phone' => '359888123456'],
            'total_weight'       => 1,
            'packages'           => [['tracking_number' => '459073686609-1', 'weight' => 1]],
            'pricing'            => [
                'payer'           => 'sender',
                'courier_service' => 2.21,
                'fees'            => [
                    ['code' => 'paper_return_receipt', 'name' => 'Обратна разписка', 'amount' => 2.2, 'quantity' => 1],
                    ['code' => 'fuel_fee', 'name' => 'Такса гориво', 'amount' => 0.38, 'quantity' => 1],
                    ['code' => 'cod_fee', 'name' => 'Наложен платеж', 'amount' => 1.5, 'quantity' => 1],
                    ['code' => null, 'name' => 'Carried over', 'amount' => 0.5, 'quantity' => 1],
                ],
                'total'           => 12.94,
                'payment_status'  => 'pending',
            ],
            'cod'                => ['amount' => 100, 'currency' => 'EUR', 'payment_status' => 'pending'],
            'related_shipments'  => [['reference_number' => '459000000001', 'relation' => 'original']],
            'quick_actions'      => ['edit'],
        ];
    }

    public function testGetReadsTheShipmentAndItsFees(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => $this->details()])]);

        $details = (new Shipments($client))->get('459073686609');

        $this->assertSame('GET', $client->calls[0]['method']);
        $this->assertSame('shipments/459073686609', $client->calls[0]['path']);
        $this->assertSame('459073686609', $details->getReferenceNumber());
        $this->assertSame('ORDER-1', $details->getExternalReference());
        $this->assertSame('shipment_registered', $details->getStatus()->getCode());
        $this->assertSame('sender', $details->getPayer());
        $this->assertSame(2.21, $details->getCourierService());
        $this->assertSame(12.94, $details->getTotal());
        $this->assertSame(100.0, $details->getCodAmount());
        $this->assertCount(4, $details->getFees());
        $this->assertSame(1.5, $details->getFee('cod_fee')->getAmount());
        $this->assertSame(1, $details->getFee('cod_fee')->getQuantity());
        $this->assertNull($details->getFee('declared_value'));
        $this->assertTrue($details->hasFee('fuel_fee'));
        $this->assertNull($details->getFees()[3]->getCode(), 'a row without a linked service has no code');
        $this->assertSame(4.58, $details->getFeesTotal());
        $this->assertSame('459000000001', $details->getOriginalReference());
        $this->assertSame(['edit'], $details->toArray()['quick_actions'], 'undocumented fields are kept');
    }

    public function testGetNormalisesAPlainStatusName(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => [
            'reference_number' => '1',
            'status'           => 'Регистрирана пратка',
            'status_code'      => 'shipment_registered',
        ]])]);

        $details = (new Shipments($client))->get('1');

        $this->assertSame('shipment_registered', $details->getStatus()->getCode());
        $this->assertSame('Регистрирана пратка', $details->getStatus()->getName());
        $this->assertSame(['code' => 'shipment_registered', 'name' => 'Регистрирана пратка'], $details->toArray()['status']);
    }

    public function testReferencesAreUrlEncodedInThePath(): void
    {
        $client = new ScriptedHttpClient();
        $shipments = new Shipments($client);

        $shipments->get('A B/1');
        $shipments->track('A B/1');
        $shipments->update('A B/1', ['delivery_type' => 'office']);

        $this->assertSame('shipments/A%20B%2F1', $client->calls[0]['path']);
        $this->assertSame('shipments/A%20B%2F1/track', $client->calls[1]['path']);
        $this->assertSame('shipments/A%20B%2F1', $client->calls[2]['path']);
    }

    public function testUpdateSendsThePutBodyAndReadsTheUpdatedShipment(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json([
            'success' => true,
            'message' => 'Пратката е актуализирана успешно',
            'data'    => $this->details(),
        ])]);

        $request = (new CreateShipmentRequest('Иван Петров', '0888123456', 'office', 'office', 'standard', 'sender'))
            ->setPickupOfficeId(149)
            ->setDeliveryOfficeId(1002)
            ->addPackage(['weight' => 2.5, 'length' => 30, 'width' => 20, 'height' => 15])
            ->addInventoryItem('Книга', 1)
            ->setServiceCode('cod_amount', 50.0);

        $details = (new Shipments($client))->update('459073686609', $request);

        $this->assertSame('PUT', $client->calls[0]['method']);
        $this->assertSame(1002, $client->calls[0]['body']['delivery_office_id']);
        $this->assertSame(['cod_amount' => 50.0], $client->calls[0]['body']['service_codes']);
        $this->assertSame([['description' => 'Книга', 'quantity' => 1]], $client->calls[0]['body']['inventory_items']);
        $this->assertSame(1.5, $details->getFee('cod_fee')->getAmount());
    }

    public function testUpdateOfAShipmentNoLongerRegisteredSurfacesTheRefusal(): void
    {
        $client = new ScriptedHttpClient([new ValidationException('Пратката не може да бъде редактирана', [], 422)]);

        $this->expectException(ValidationException::class);
        (new SleeplessShipments($client))->update('1', ['delivery_type' => 'office']);
    }

    public function testTrackBulkCleansTheReferencesAndKeysEntriesByString(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => [
            '457341228749' => [
                'found'       => true,
                'reference_number' => '457341228749',
                'status'      => 'Непотърсена',
                'status_code' => 'shipment_untracked',
                'tracking'    => [
                    ['status' => 'Регистрирана пратка', 'status_code' => 'shipment_registered', 'created_at' => 'a'],
                    ['status' => ['code' => 'shipment_untracked', 'name' => 'Непотърсена'], 'created_at' => 'b'],
                ],
                'chain_before' => [],
                'chain_after'  => [['reference_number' => '457344405725', 'status' => ['code' => 'shipment_returning_to_sender', 'name' => 'Връщане']]],
            ],
            '457343604650' => ['found' => false, 'error' => 'Пратката не е намерена.'],
        ], 'meta' => ['requested' => 2, 'found' => 1, 'not_found' => 1]])]);

        $entries = (new Shipments($client))->trackBulk([' 457341228749 ', '457341228749', '', null, '457343604650']);

        $this->assertSame('POST', $client->calls[0]['method']);
        $this->assertSame('shipments/track/bulk', $client->calls[0]['path']);
        $this->assertSame(['references' => ['457341228749', '457343604650']], $client->calls[0]['body']);

        $this->assertSame(['457341228749', '457343604650'], array_map('strval', array_keys($entries)));
        $found = $entries['457341228749'];
        $this->assertInstanceOf(BulkTrackingEntry::class, $found);
        $this->assertTrue($found->isFound());
        $this->assertSame('shipment_untracked', $found->getStatusCode());
        $this->assertSame('Непотърсена', $found->getStatusName());
        $this->assertSame('shipment_untracked', $found->getLatestEvent()['status_code']);
        $this->assertSame('Непотърсена', $found->getLatestEvent()['status'], 'an object status on an event is flattened');
        $this->assertSame('shipment_returning_to_sender', $found->getChainAfter()[0]['status_code']);

        $missing = $entries['457343604650'];
        $this->assertFalse($missing->isFound());
        $this->assertSame('457343604650', $missing->getReferenceNumber(), 'taken from the key when the entry has none');
        $this->assertSame('Пратката не е намерена.', $missing->getError());
    }

    public function testTrackBulkSendsAtMostOneHundredReferencesPerRequest(): void
    {
        $references = array_map(static function (int $i): string {
            return (string) (457000000000 + $i);
        }, range(1, 250));

        $client = new ScriptedHttpClient();
        (new Shipments($client))->trackBulk($references);

        $this->assertCount(3, $client->calls);
        $this->assertCount(100, $client->calls[0]['body']['references']);
        $this->assertCount(100, $client->calls[1]['body']['references']);
        $this->assertCount(50, $client->calls[2]['body']['references']);
        $this->assertSame('457000000101', $client->calls[1]['body']['references'][0]);
    }

    public function testTrackBulkWithNothingToTrackSendsNothing(): void
    {
        $client = new ScriptedHttpClient();

        $this->assertSame([], (new Shipments($client))->trackBulk(['', '  ']));
        $this->assertCount(0, $client->calls);
    }

    public function testCreateRepeatsAConflictWithTheSameBodyAndReportsTheRepeat(): void
    {
        $client = new ScriptedHttpClient([
            new ConflictException('in progress', 3),
            ScriptedHttpClient::json(['success' => true, 'data' => ['reference_number' => '455791936383']], 201, ['X-Duplicate-Request' => ['true']]),
        ]);
        $shipments = new SleeplessShipments($client);

        $shipment = $shipments->create(['receiver_name' => 'Иван', 'packages' => []]);

        $this->assertSame('455791936383', $shipment->getReferenceNumber());
        $this->assertCount(2, $client->calls);
        $this->assertSame($client->calls[0]['body'], $client->calls[1]['body'], 'the repeat must be identical');
        $this->assertSame([3000000], $shipments->pauses, 'waits what Retry-After asks');
        $this->assertSame(AbstractResource::REPLAY_REPEAT, $shipments->getLastReplay());
        $this->assertTrue($shipments->wasLastReplayed());
    }

    public function testAReplayOfAnEarlierRequestIsReported(): void
    {
        $client = new ScriptedHttpClient([
            ScriptedHttpClient::json(['success' => true, 'data' => ['reference_number' => '1', 'status' => 'Регистрирана пратка']], 201, ['x-duplicate-request' => 'true']),
            ScriptedHttpClient::json(['success' => true, 'data' => ['reference_number' => '2']], 201),
        ]);
        $shipments = new SleeplessShipments($client);

        $shipment = $shipments->create(['receiver_name' => 'Иван']);
        $this->assertSame(AbstractResource::REPLAY_EARLIER, $shipments->getLastReplay());
        $this->assertSame('Регистрирана пратка', $shipment->getStatus()->getName(), 'a plain status name is kept');

        $shipments->create(['receiver_name' => 'Петър']);
        $this->assertSame(AbstractResource::REPLAY_NONE, $shipments->getLastReplay(), 'every write resets it');
    }

    public function testAConflictThatPersistsIsThrownAfterTheRepeats(): void
    {
        $client = new ScriptedHttpClient([
            new ConflictException('in progress', 0),
            new ConflictException('in progress', 60),
            new ConflictException('in progress', 5),
        ]);
        $shipments = new SleeplessShipments($client);

        try {
            $shipments->create(['receiver_name' => 'Иван']);
            $this->fail('expected a ConflictException');
        } catch (ConflictException $e) {
            $this->assertSame(5, $e->getRetryAfter());
        }

        $this->assertCount(3, $client->calls, 'the first attempt and two repeats');
        $this->assertSame([5000000, 8000000], $shipments->pauses, 'default 5 s when none is given, capped at 8 s');
    }

    public function testRepeatingCanBeTurnedOff(): void
    {
        $client = new ScriptedHttpClient([new ConflictException('in progress', 5)]);
        $shipments = (new SleeplessShipments($client))->setConflictRetries(0);

        $this->expectException(ConflictException::class);
        try {
            $shipments->create(['receiver_name' => 'Иван']);
        } finally {
            $this->assertCount(1, $client->calls);
        }
    }

    public function testCancelIsNotRepeatedOnAConflict(): void
    {
        $client = new ScriptedHttpClient([new ConflictException('in progress', 5)]);

        $this->expectException(ConflictException::class);
        try {
            (new SleeplessShipments($client))->cancel('459073686609');
        } finally {
            $this->assertCount(1, $client->calls);
            $this->assertSame('shipments/459073686609/cancel', $client->calls[0]['path']);
        }
    }

    public function testCreateValidatesARequestObject(): void
    {
        $client = new ScriptedHttpClient();
        $request = new CreateShipmentRequest('', '', 'office', 'office', 'standard', 'sender');

        $this->expectException(\InvalidArgumentException::class);
        try {
            (new Shipments($client))->create($request);
        } finally {
            $this->assertCount(0, $client->calls, 'nothing is sent for an invalid request');
        }
    }
}

/**
 * Shipments with the waits recorded instead of slept.
 */
class SleeplessShipments extends Shipments
{
    /** @var int[] */
    public $pauses = [];

    protected function pause($microseconds)
    {
        $this->pauses[] = (int) $microseconds;
    }
}
